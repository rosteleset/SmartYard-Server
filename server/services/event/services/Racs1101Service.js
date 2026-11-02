import net from 'node:net';
import { timingSafeEqual } from 'node:crypto';
import { Aedes } from 'aedes';
import { API, getTimestamp } from '../utils/index.js';

const TOPIC_PATTERN = /^access_control\/([0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12})\/([^/]+)$/;
const MAX_PAYLOAD_SIZE = 65536;

class Racs1101Service {
    constructor(unit, config) {
        this.unit = unit;
        this.config = config;

        if (!Number.isInteger(config.port) || config.port < 1 || config.port > 65535) {
            throw new Error('Invalid RACS-1101 listener configuration');
        }

        if (typeof config.username !== 'string' || config.username.length === 0
            || typeof config.password !== 'string' || config.password.length === 0) {
            throw new Error('RACS-1101 MQTT username and password must be configured');
        }

        this.sockets = new Set();
        this.stopping = false;
        this.broker = null;
        this.server = null;
        this.stopPromise = null;
        this.shutdown = () => this.stop().catch(error => {
            console.error(`${this.unit} shutdown error: ${error.message}`);
            process.exitCode = 1;
        });
    }

    async start() {
        this.broker = await Aedes.createBroker();

        this.broker.preConnect = (client, packet, callback) => {
            const topic = packet.will?.topic.match(TOPIC_PATTERN);
            client.racsUuid = topic?.[1] ?? null;
            callback(null, !packet.will || (topic?.[2] === 'status' && packet.will.payload.length <= MAX_PAYLOAD_SIZE));
        };

        this.broker.authenticate = (client, username, password, callback) => {
            const secret = Buffer.from(this.config.password);
            const supplied = password ?? Buffer.alloc(0);
            const accepted = username === this.config.username && supplied.length === secret.length
                && timingSafeEqual(secret, supplied);

            if (accepted) {
                client.racsIp = client.conn.remoteAddress.replace(/^::ffff:/, '');
            }

            callback(null, accepted);
        };

        this.broker.authorizeSubscribe = (client, subscription, callback) => {
            const topic = subscription.topic.match(TOPIC_PATTERN);
            const allowed = topic?.[2] === 'requests' && (!client.racsUuid || topic[1] === client.racsUuid);

            if (allowed) {
                client.racsUuid = topic[1];
            }

            callback(null, allowed ? subscription : null);
        };

        // Aedes acknowledges QoS 1 before its publish event; handle messages in this hook instead.
        this.broker.authorizePublish = (client, packet, callback) => {
            if (!client?.racsIp) {
                callback(new Error('Unauthenticated RACS-1101 publisher'));
                return;
            }

            this.handleMessage(client, packet).then(() => callback(null), callback);
        };

        this.broker.on('clientReady', client => console.log(`${this.unit} || ${client.racsIp} || connected`));

        this.broker.on('clientError', (client, error) => {
            console.error(`${this.unit} || ${client?.racsIp ?? 'unknown'} || ${error.message}`);
        });

        this.broker.on('connectionError', (client, error) => console.error(`${this.unit} || ${error.message}`));

        this.server = net.createServer(socket => {
            if (this.stopping) {
                socket.destroy();
                return;
            }

            this.sockets.add(socket);
            socket.on('close', () => this.sockets.delete(socket));
            this.broker.handle(socket);
        });

        try {
            await new Promise((resolve, reject) => {
                this.server.once('error', reject);
                this.server.listen(this.config.port, this.config.host ?? '0.0.0.0', () => {
                    this.server.removeListener('error', reject);
                    resolve();
                });
            });
        } catch (error) {
            await this.stop();
            throw error;
        }

        this.server.on('error', error => console.error(`${this.unit} || ${error.message}`));
        process.once('SIGINT', this.shutdown);
        process.once('SIGTERM', this.shutdown);
        console.log(`${this.unit} MQTT listener running on ${this.config.host ?? '0.0.0.0'}:${this.config.port}`);
    }

    async handleMessage(client, packet) {
        const receivedAt = getTimestamp(new Date());
        const payload = packet.payload.toString();
        const msg = JSON.stringify({ topic: packet.topic, payload });

        console.log(`${receivedAt} || ${client.racsIp} || ${msg}`);

        if (packet.payload.length > MAX_PAYLOAD_SIZE) {
            throw new Error('RACS-1101 message is too large');
        }

        // sendLog escapes quotes in SQL literals, but leaves backslashes unchanged.
        await API.sendLog({ date: receivedAt, ip: client.racsIp, unit: this.unit, msg: msg.replace(/\\/g, '\\\\') });

        const topic = packet.topic.match(TOPIC_PATTERN);

        if (topic && !client.racsUuid) {
            client.racsUuid = topic[1];
        }

        if (!topic || topic[1] !== client.racsUuid || topic[2] === 'requests') {
            throw new Error('RACS-1101 may publish only in its own non-command topics');
        }

        if (topic[2] !== 'updates' || packet.retain) {
            return;
        }

        await this.handleEvent(receivedAt, client.racsIp, client.racsUuid, payload);
    }

    async handleEvent(receivedAt, ip, uuid, payload) {
        let message;
        try {
            message = JSON.parse(payload);
        } catch {
            return;
        }

        const params = message?.params;
        if (!params || params.uuid !== uuid || ![1, 2].includes(params.relay_index)) {
            return;
        }

        /*
         * Use server receipt time because the controller RTC may reset on reboot.
         * Buffered events have no reliable replay marker, so they are processed too,
         * but their plog date is the delivery time, not the original passage time.
         * The original controller timestamp remains available in raw logs.
         */
        const event = { date: receivedAt, ip, door: params.relay_index - 1 };

        switch (message.method) {
            case 'card_access':
                if (
                    params.access_granted !== true ||
                    typeof params.key !== 'string' ||
                    !/^[0-9a-f]{1,14}$/i.test(params.key)
                ) {
                    return;
                }

                event.by = 'rfid';
                event.detail = params.key.toUpperCase().padStart(14, '0');
                break;
            // TODO: Record exit-button events in plog without triggering FALPRS motion detection suspension.
            // case 'door_open':
            //     event.by = 'button';
            //     event.detail = params.relay_index === 1 ? 'main' : 'second';
            //     break;
            default:
                return;
        }

        await API.openDoor(event);
    }

    stop() {
        if (this.stopPromise) {
            return this.stopPromise;
        }

        this.stopping = true;
        this.stopPromise = (async () => {
            process.removeListener('SIGINT', this.shutdown);
            process.removeListener('SIGTERM', this.shutdown);

            const serverClosed = new Promise(resolve => {
                if (this.server?.listening) {
                    this.server.close(resolve);
                } else {
                    resolve();
                }
            });

            for (const socket of this.sockets) {
                socket.destroy();
            }

            if (this.broker) {
                await new Promise(resolve => this.broker.close(resolve));
            }

            await serverClosed;
        })();

        return this.stopPromise;
    }
}

export { Racs1101Service };
