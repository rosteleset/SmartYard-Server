#!/usr/bin/node

var app_bundle_id = 'bla-bla-bla';
var app_user_agent = 'bla-bla-bla';
var database = 'bla-bla-bla';

var path = require('path');
var app = require('express')();
var admin = require('firebase-admin');
var cert = path.join(__dirname, 'bla-bla-bla');
var { Curl } = require('node-libcurl');

function pushOk(token, result, res) {
    if (result && result.successCount && parseInt(result.successCount)) {
        console.log((new Date()).toLocaleString() + " ok: " + token);
        if (result.results && result.results[0] && result.results[0].messageId) {
            res.send('OK:' + result.results[0].messageId);
        } else {
            res.send('OK');
        }
    } else {
        pushFail(token, result, res);
    }
}

function pushFail(token, error, res) {
    console.log((new Date()).toLocaleString() + " err: " + token);

    let broken = false;

    console.log(error);

    if (error && error.results && error.results.length && error.results[0] && error.results[0].error && error.results[0].error.code) {
        if (error.results[0].error.code == 'messaging/registration-token-not-registered') {
            broken = true;
        }
    }

    if (error && error.errorData) {
        if (error.errorData.indexOf('BadDeviceToken') >= 0) {
            broken = true;
        }
    }

    if (broken) {
        res.send('ERR:broken');
    } else {
        res.send('ERR:send');
    }
}

function realPush(msg, data, options, token, type, res) {
    switch (parseInt(type)) {
        case 0:
        case 3:
            let message = {
                notification: msg,
                data: data,
            };

            if (options) {
                admin.messaging().sendToDevice(token, message, options).then(r => {
                    pushOk(token, r, res);
                }).catch(e => {
                    pushFail(token, e, res);
                });
            } else {
                admin.messaging().sendToDevice(token, message).then(r => {
                    pushOk(token, r, res);
                }).catch(e => {
                    pushFail(token, e, res);
                });
            }
            break;
        case 1:
        case 2:
            let http2_server = (parseInt(type) == 2)?'https://api.sandbox.push.apple.com':'https://api.push.apple.com';

            console.log(http2_server);

            let curl = new Curl();

            curl.setOpt(Curl.option.HTTP_VERSION, 3);
            curl.setOpt(Curl.option.URL, `${http2_server}/3/device/${token}`);
            curl.setOpt(Curl.option.PORT, 443);
            curl.setOpt(Curl.option.HTTPHEADER, [
                `apns-topic: ${app_bundle_id}.voip`,
                `apns-push-type: voip`,
                `User-Agent: ${app_user_agent}`,
            ]);
            curl.setOpt(Curl.option.POST, true);
            curl.setOpt(Curl.option.POSTFIELDS, JSON.stringify({
                data: data,
            }));
            curl.setOpt(Curl.option.TIMEOUT, 30);
            curl.setOpt(Curl.option.SSL_VERIFYPEER, false);
            curl.setOpt(Curl.option.SSLCERT, cert);
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
            timestamp: Math.round((new Date()).getTime()/1000).toString(),
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

        if (req.query.platform == 'ios') {
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

        if (req.query.platform == 'android') {
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
app.listen(8080, '127.0.0.1');

admin.initializeApp({
    credential: admin.credential.cert(require(path.join(__dirname, 'pushServiceAccountKey.json'))),
    databaseURL: database,
});
