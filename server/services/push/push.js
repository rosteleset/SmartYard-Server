#!/usr/bin/node

require("dotenv").config({
    path: `${process.env.NODE_ENV === "development" ? ".env_development" : ".env"}`
});

const path = require('path');
const app = require('express')();
const admin = require('firebase-admin');
const { Curl } = require('node-libcurl');

const CERT = path.join(__dirname, './assets/certificate-and-privatekey.pem');
const SERVICE_ACCOUNT = path.join(__dirname, './assets/pushServiceAccountKey.json');
const PORT = process.env.APP_PORT || 8080;
const HOST = process.env.APP_HOST || "127.0.0.1";
const APP_PROJECT_NAME = process.env.APP_PROJECT_NAME || "example_app_project_name";
const APP_BUNDLE_ID = process.env.APP_BUNDLE_ID || "example_app_bundle_id";
const APP_USER_AGENT = process.env.APP_USER_AGENT || 'example_app_user_agent';
const DB_NAME = process.env.APP_DATABASE_NAME || 'example_database';

const pushOk = (token, result, res) => {
    console.log(`${(new Date()).toLocaleString()} | pushOk | result: ${JSON.stringify(result)}`)

    if (result && result.successCount && parseInt(result.successCount)) {
        console.log(`${(new Date()).toLocaleString()} | pushOk | ${token}`);
        if (result.results && result.results[0] && result.results[0].messageId) {
            res.send('OK:' + result.results[0].messageId);
        } else {
            res.send('OK');
        }
        return;
    }

    if (result && result.toString() && result.indexOf(`projects/${APP_PROJECT_NAME}/messages/`) === 0) {
        console.log(`${(new Date()).toLocaleString()} | pushOk | >>> `);
        res.send('OK');
        return;
    }

    pushFail(token, result, res);
}

const pushFail = (token, error, res) => {
    console.log((new Date()).toLocaleString() + " err: " + token);

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

    if (broken) {
        res.send('ERR:broken');
    } else {
        res.send('ERR:send');
    }
}

const realPush = (msg, data, options, token, type, res) => {
    let message;

    switch (parseInt(type)) {
        case 1:
        case 2:
            let http2_server = (parseInt(type) == 2) ? 'https://api.sandbox.push.apple.com' : 'https://api.push.apple.com';

            console.log(http2_server);

            let curl = new Curl();

            curl.setOpt(Curl.option.HTTP_VERSION, 3);
            curl.setOpt(Curl.option.URL, `${http2_server}/3/device/${token}`);
            curl.setOpt(Curl.option.PORT, 443);
            curl.setOpt(Curl.option.HTTPHEADER, [
                `apns-topic: ${APP_BUNDLE_ID}.voip`,
                `apns-push-type: voip`,
                `apns-expiration: ${parseInt((new Date()).getTime() / 1000) + 60}`,
                `User-Agent: ${APP_USER_AGENT}`,
            ]);
            curl.setOpt(Curl.option.POST, true);
            curl.setOpt(Curl.option.POSTFIELDS, JSON.stringify({
                data: data,
            }));
            curl.setOpt(Curl.option.TIMEOUT, 30);
            curl.setOpt(Curl.option.SSL_VERIFYPEER, false);
            curl.setOpt(Curl.option.SSLCERT, CERT);
            curl.setOpt(Curl.option.HEADER, true);
            curl.setOpt(Curl.option.VERBOSE, false);

            curl.on('end', (code, data, headers) => {
                if (parseInt(code) === 200) {
                    pushOk(token, { successCount: 1 }, res);
                } else {
                    pushFail(token, { errorCode: code, errorData: data, errorHeaders: headers }, res);
                }
                curl.close();
            });

            curl.on('error', () => {
                curl.close();
            });

            curl.perform();
            break;

        case 0:
        case 3:
            let badge = 0;

            if (msg) {
                delete msg.tag;
                if (msg.badge) {
                    badge = parseInt(msg.badge);
                }
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
            } else {
                message.android.notification.notification_count = badge;
            }

            admin.messaging().send(message).then(r => {
                pushOk(token, r, res);
            }).catch(e => {
                pushFail(token, e, res);
            });
            break;

        default:
            console.log('Bad push type');
            break;
    }
}

app.get('/push', function (req, res) {
    console.log((new Date()).toLocaleString(), req.query);

    let pushed = false;

    if (req.query.hash || req.query.pass) {
        let data = {
            timestamp: Math.round((new Date()).getTime() / 1000).toString(),
        };

        let fields = [
            "server",
            "port",
            "transport",
            "ttl",
            "callerId",
            "platform",
            "flatId",
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

        data.dtmf = req.query.dtmf?req.query.dtmf:'1';

        if (req.query.turn) {
            data.turn = req.query.turn;
            data.turnTransport = req.query.turnTransport;
        }

        if (req.query.stun) {
            data.stun = req.query.stun;
            data.stunTransport = req.query.stunTransport;
        }

        console.log((new Date()).toLocaleString(), data);

        if (req.query.platform === 'ios') {
            realPush({
                title: req.query.title?req.query.title:"Incoming call",
                body: req.query.callerId?req.query.callerId:"Unknown",
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

    if (req.query.msg) {
        realPush({
            title: req.query.title,
            body: req.query.msg,
            badge: req.query.badge?req.query.badge:'1',
            sound: "default",
        }, {
            messageId: req.query.messageId?req.query.messageId:'',
            badge: req.query.badge?req.query.badge:'1',
            action: req.query.pushAction?req.query.pushAction:'inbox',
        }, {
            priority: 'high',
            mutableContent: false,
        }, req.query.token, 0, res);
        pushed = true;
    }

    if (!pushed) {
        res.send('UNK');
    }
});

// runIt!
app.use(require('body-parser').urlencoded({ extended: true }));
app.listen(PORT, HOST, () => {
    console.log(`${(new Date()).toLocaleString()} | Push server started >> http://${HOST}:${PORT}`)
})
    .on("listening", () => {
        admin.initializeApp({
            credential: admin.credential.cert(SERVICE_ACCOUNT),
            databaseURL: DB_NAME,
        });
    })