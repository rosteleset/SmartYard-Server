#!/bin/env node

const express = require('express');
const app = express();
const mqtt = require('mqtt');
const redis = require('redis').createClient();
const fs = require('fs');

const config = JSON.parse(fs.readFileSync(__dirname + "/../../config/config.json").toString());

const client = mqtt.connect(config.ws, {
    username: config.backends.mqtt.username,
    password: config.backends.mqtt.password,
});

function redisInit() {
    redis.sendCommand([ 'config', 'set', 'notify-keyspace-events', 'Ex' ]).then(() => {
        redis.subscribe('__keyevent@0__:expired', (k, e) => {
            if (e == '__keyevent@0__:expired') {
                client.publish("redis/expire", JSON.stringify({ key: k }));
            }
        });
    });
}

redis.connect().then(() => {
    if (config && config.redis && config.redis.password) {
        redis.auth(config.redis.password).then(redisInit);
    } else {
        redisInit();
    }
});

app.post('/broadcast', express.json({ type: '*/*' }), (req, res) => {
    if (req.body && req.body.topic && req.body.payload) {
        client.publish(req.body.topic, JSON.stringify(req.body.payload));
    }
    res.status(200).send("OK").end();
});

app.use(require('body-parser').urlencoded({ extended: true }));
app.listen(8082, '127.0.0.1');

process.on('SIGINT', () => {
    console.log("\nGracefully shutting down from SIGINT (Ctrl-C)");
    process.exit(0);
});