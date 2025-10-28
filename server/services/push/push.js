const app = require('express')();
const admin = require('firebase-admin');
const { Curl } = require('node-libcurl');

const {
    PORT,
    HOST,

    FCM_PROJECT_NAME,
    FCM_DATABASE_NAME,
    FCM_SERVICE_ACCOUNT,

    APN_BUNDLE_ID,
    APN_USER_AGENT,
    APN_CERT,

    HUAWEI_CLIENT_ID,
    HUAWEI_CLIENT_SECRET,
    HUAWEI_PROJECT_ID,

    RUSTORE_PROJECT_ID,
    RUSTORE_TOKEN
} = require('./constants.js');

let huaweiToken = '';

const pushOk = (token, result, res) => {
    console.log(`${(new Date()).toLocaleString()} | pushOk | result: ${JSON.stringify(result)}`)

    if (result && result.successCount && parseInt(result.successCount)) {
        if (result.results && result.results[0] && result.results[0].messageId) {
            res.send('OK: ' + result.results[0].messageId);
        } else {
            res.send('OK');
        }
        return;
    }

    if (result && result.toString() && result.indexOf(`projects/${FCM_PROJECT_NAME}/messages/`) === 0) {
        res.send('OK');
        return;
    }

    pushFail(token, result, res);
}

const pushFail = (token, error, res) => {
    console.log(`${(new Date()).toLocaleString()} |  pushErr | token: ${token}`);

    let broken = false;

    console.log(JSON.stringify(error));

    if (error && error.results && error.results.length && error.results[0] && error.results[0].error && error.results[0].error.code) {
        if (error.results[0].error.code === 'messaging/registration-token-not-registered') {
            broken = true;
        }
    }

    if (error && error.errorData) {
        if (error.errorData.indexOf('BadDeviceToken') >= 0) {
            broken = true;
        }
    }

    if (error && error.errorInfo && error.errorInfo.code && error.errorInfo.code === 'messaging/registration-token-not-registered') {
        broken = true;
    }

    if (error.huaweiErrorData && error.huaweiErrorData.code && error.huaweiErrorData.code === "80300007") {
        broken = true;
    }

    if (broken) {
        res.send('ERR:broken');
    } else {
        res.send('ERR:send');
    }
}

const realPush = (msg, data, options, token, type, res) => {
    let message;
    let badge = 0;
    let curl = new Curl();

    switch (parseInt(type)) {
        case 0:
        case 3:
            if (msg) {
                delete msg.tag;
                delete msg.badge;
                delete msg.sound;
            }

            message = {
                notification: msg,
                data: data,
                android: {
                    notification: {
                    },
                    priority: "HIGH",
                    ttl: 30000,
                },
                apns: {
                    headers: {
                        'apns-collapse-id': 'voip'
                    },
                    payload: {
                        aps: {
                            'mutable-content': 1,
                            'badge': badge,
                            'sound': 'default',
                            'interruption-level': 'time-sensitive',
                        },
                    },
                },
                token: token,
            };

            if (!msg || !Object.keys(msg).length) {
                delete message.notification;
            }

            if (!options || !options.mutableContent) {
                delete message.apns.payload.aps['mutable-content'];
            }

            if (!badge) {
                delete message.apns.payload.aps['badge'];
            }

            admin.messaging().send(message).then(r => {
                pushOk(token, r, res);
            }).catch(e => {
                pushFail(token, e, res);
            });
            break;

        case 1:
        case 2:
            let http2_server = (parseInt(type) === 2) ? 'https://api.sandbox.push.apple.com' : 'https://api.push.apple.com';

            curl.setOpt(Curl.option.HTTP_VERSION, 3);
            curl.setOpt(Curl.option.URL, `${http2_server}/3/device/${token}`);
            curl.setOpt(Curl.option.PORT, 443);
            curl.setOpt(Curl.option.HTTPHEADER, [
                `apns-topic: ${APN_BUNDLE_ID}.voip`,
                `apns-push-type: voip`,
                `apns-expiration: ${parseInt((new Date()).getTime() / 1000) + 60}`,
                `User-Agent: ${APN_USER_AGENT}`,
            ]);
            curl.setOpt(Curl.option.POST, true);
            curl.setOpt(Curl.option.POSTFIELDS, JSON.stringify({
                data: data,
            }));
            curl.setOpt(Curl.option.TIMEOUT, 30);
            curl.setOpt(Curl.option.SSL_VERIFYPEER, false);
            curl.setOpt(Curl.option.SSLCERT, APN_CERT);
            curl.setOpt(Curl.option.HEADER, true);
            curl.setOpt(Curl.option.VERBOSE, false);

            curl.on('end', (code, data, headers) => {
                if (parseInt(code) === 200) {
                    pushOk(token, { successCount: 1 }, res);
                } else {
                    pushFail(token, { errorCode: code, errorData: data, errorHeaders: headers }, res);
                }
                curl.close();
                delete curl;
            });

            curl.on('error', () => {
                curl.close();
                delete curl;
            });

            curl.perform();
            break;

        case 4:
            let huaweiServer = `https://push-api.cloud.huawei.com/v2/${HUAWEI_PROJECT_ID}/messages:send`;

            if (msg && msg.title && msg.body) {
                data.title = msg.title;
                data.body = msg.body;
            }

            message = {
                data: JSON.stringify(data),
                android: {
                    ttl: "30s",
                },
                token: [
                    token,
                ]
            };

            if (msg && Object.keys(msg).length) {
                delete message.android.ttl;
            }

            message = {
                validate_only: false,
                message: message,
            };

            curl.setOpt(Curl.option.URL, huaweiServer);
            curl.setOpt(Curl.option.HTTPHEADER, [
                `Authorization: Bearer ${huaweiToken}`,
            ]);
            curl.setOpt(Curl.option.POST, true);
            curl.setOpt(Curl.option.POSTFIELDS, JSON.stringify(message));
            curl.setOpt(Curl.option.TIMEOUT, 30);
            curl.setOpt(Curl.option.SSL_VERIFYPEER, false);
            curl.setOpt(Curl.option.HEADER, false);
            curl.setOpt(Curl.option.VERBOSE, false);

            curl.on('end', (code, data, headers) => {
                if (parseInt(code) === 200) {
                    try {
                        data = JSON.parse(data);
                    } catch (_) {
                        data = false;
                    }
                    if (data && data.code === "80000000") {
                        pushOk(token, { successCount: 1 }, res);
                    } else {
                        pushFail(token, { errorCode: code, huaweiErrorData: data, errorHeaders: headers }, res);
                    }
                } else {
                    pushFail(token, { errorCode: code, huaweiErrorData: data, errorHeaders: headers }, res);
                }
                curl.close();
                delete curl;
            });

            curl.on('error', () => {
                curl.close();
                delete curl;
            });

            curl.perform();
            break;

        case 5:
            let rustoreServer = `https://vkpns.rustore.ru/v1/projects/${RUSTORE_PROJECT_ID}/messages:send`;

            if (msg && msg.title && msg.body) {
                data.title = msg.title;
                data.body = msg.body;
            }

            message = {
                android: {
                    ttl: "30s",
                },
                data: data,
                token: token,
            };

            if (msg && Object.keys(msg).length) {
                delete message.android.ttl;
            }

            message = {
                message: message,
            };

            curl.setOpt(Curl.option.URL, rustoreServer);
            curl.setOpt(Curl.option.HTTPHEADER, [
                `Authorization: Bearer ${RUSTORE_TOKEN}`,
            ]);
            curl.setOpt(Curl.option.POST, true);
            curl.setOpt(Curl.option.POSTFIELDS, JSON.stringify(message));
            curl.setOpt(Curl.option.TIMEOUT, 30);
            curl.setOpt(Curl.option.SSL_VERIFYPEER, false);
            curl.setOpt(Curl.option.HEADER, false);
            curl.setOpt(Curl.option.VERBOSE, false);

            curl.on('end', (code, data, headers) => {
                if (parseInt(code) === 200) {
                    pushOk(token, { successCount: 1 }, res);
                } else {
                    pushFail(token, { errorCode: code, rustoreErrorData: data.toString(), errorHeaders: headers }, res);
                }
                curl.close();
                delete curl;
            });

            curl.on('error', () => {
                curl.close();
                delete curl;
            });

            curl.perform();
            break;

        default:
            console.log(`${new Date().toLocaleString()} | Bad push type`);
            break;
    }
}

const refreshHuaweiToken = () => {
    let curl = new Curl();

    curl.setOpt(Curl.option.URL, `https://oauth-login.cloud.huawei.com/oauth2/v3/token?grant_type=client_credentials&client_id=${HUAWEI_CLIENT_ID}&client_secret=${HUAWEI_CLIENT_SECRET}`);
    curl.setOpt(Curl.option.TIMEOUT, 30);
    curl.setOpt(Curl.option.SSL_VERIFYPEER, false);
    curl.setOpt(Curl.option.HEADER, false);
    curl.setOpt(Curl.option.VERBOSE, false);

    curl.on('end', (code, data, headers) => {
        if (parseInt(code) === 200) {
            try {
                data = JSON.parse(data);
                huaweiToken = data.access_token;
                console.log(`${new Date().toLocaleString()} | HuaweiToken: ` + huaweiToken);
                setTimeout(refreshHuaweiToken, data.expires_in * 500);
            } catch (e) {
                console.log(e);
                setTimeout(refreshHuaweiToken, 1000);
            }
        } else {
            console.log("error code: " + code);
            setTimeout(refreshHuaweiToken, 1000);
        }
        curl.close();
        delete curl;
    });

    curl.on('error', e => {
        console.log(e);
        curl.close();
        delete curl;
        setTimeout(refreshHuaweiToken, 1000);
    });

    curl.perform();
}

const initFirebase = async () => {
    try {
        admin.initializeApp({
            credential: admin.credential.cert(FCM_SERVICE_ACCOUNT),
            databaseURL: FCM_DATABASE_NAME,
        });
        console.log(`${new Date().toLocaleString()} | Firebase init success`);
    } catch (error) {
        console.error(`${new Date().toLocaleString()} | Error init Firebase: `, error);
        throw error;
    }
}

const startServer = async () => {
    try {
        await initFirebase();

        app.listen(PORT, HOST, () => {
            console.log(`${new Date().toLocaleString()} | Push server started >> http://${HOST}:${PORT}`);
        });

        if (HUAWEI_PROJECT_ID && HUAWEI_CLIENT_ID && HUAWEI_CLIENT_SECRET) {
            refreshHuaweiToken();
        }
    } catch (error) {
        console.error('Error starting server: ', error);
    }
}

app.get('/push', function (req, res) {
    console.log((new Date()).toLocaleString(), req.query);

    let pushed = false;

    let data = {
        timestamp: Math.round((new Date()).getTime() / 1000).toString(),
    };

    if ((req.query.hash || req.query.pass) && !req.query.msg) {
        let fields = [
            "server",
            "port",
            "transport",
            "ttl",
            "callerId",
            "platform",
            "flatId",
            "houseId",
            "flatNumber",
            "hash",
            "pass",
            "live",
            "image",
            "domophoneId",
            "videoServer",
            "videoToken",
            "videoType",
            "videoStream",
        ];

        for (let i = 0; i < fields.length; i++) {
            if (req.query[fields[i]]) {
                data[fields[i]] = req.query[fields[i]];
            }
        }

        if (!data.pass && req.query.hash) {
            data.pass = req.query.hash;
        }

        if (!data.callerId) {
            data.callerId = "Unknown";
        }

        if (req.query.extension) {
            data.extension = req.query.extension.toString();
        }

        data.dtmf = req.query.dtmf ? req.query.dtmf : '1';

        if (req.query.turn) {
            data.turn = req.query.turn;
            data.turnTransport = req.query.turnTransport;
        }

        if (req.query.stun) {
            data.stun = req.query.stun;
            data.stunTransport = req.query.stunTransport;
        }

        if (req.query.platform === 'ios') {
            realPush({
                title: req.query.title ? req.query.title : "Incoming call",
                body: req.query.callerId ? req.query.callerId : "Unknown",
                tag: "voip",
            }, data, {
                priority: 'high',
                mutableContent: true,
                collapseKey: 'voip',
            }, req.query.token, req.query.type, res);
            pushed = true;
        }

        if (req.query.platform === 'android') {
            realPush({}, data, {
                priority: 'high',
                mutableContent: false,
            }, req.query.token, req.query.type, res);
            pushed = true;
        }
    }

    if (!req.query.type) {
        req.query.type = 0;
    }

    if (req.query.msg) {

        let fields = [
            "houseId",
            "hash",
        ];

        for (let i = 0; i < fields.length; i++) {
            if (req.query[fields[i]]) {
                data[fields[i]] = req.query[fields[i]];
            }
        }
/*
        realPush({
            title: req.query.title,
            body: req.query.msg,
            badge: req.query.badge ? req.query.badge : '1',
            sound: "default",
        }, {
            messageId: req.query.messageId ? req.query.messageId : '',
            badge: req.query.badge ? req.query.badge : '1',
            action: req.query.pushAction ? req.query.pushAction : 'inbox',
        }, {
            priority: 'high',
            mutableContent: false,
        }, req.query.token, req.query.type, res);
        pushed = true;
*/

        if (req.query.platform == 'ios') {
            realPush({
                title: req.query.title,
                body: req.query.msg,
                badge: req.query.badge ? req.query.badge : '1',
                sound: "default",
            }, {
                messageId: req.query.messageId ? req.query.messageId : '',
                badge: req.query.badge ? req.query.badge : '1',
                action: req.query.pushAction ? req.query.pushAction : 'inbox',
            }, {
                priority: 'high',
                mutableContent: false,
            }, req.query.token, req.query.type, res);
            pushed = true;
        }

        if (req.query.platform == 'android') {
            realPush({}, {
                title: req.query.title,
                body: req.query.msg,
                badge: req.query.badge ? req.query.badge : '1',
                sound: "default",
                messageId: req.query.messageId ? req.query.messageId : '',
                badge: req.query.badge ? req.query.badge : '1',
                action: req.query.pushAction ? req.query.pushAction : 'inbox',
                houseId: data.houseId ? data.houseId : '',
                hash: data.hash ? data.hash : '',
                timestamp: data.timestamp ? data.timestamp : '',
            }, {
                priority: 'high',
                mutableContent: false,
            }, req.query.token, req.query.type, res);
            pushed = true;
        }
    }

    if (!pushed) {
        res.send('UNK');
    }
});

app.use(require('body-parser').urlencoded({ extended: true }));

startServer();
