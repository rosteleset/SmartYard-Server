#!/bin/env node

const express = require('express');
const app = express();
const mqtt = require('mqtt');
const redis = require('redis').createClient();
const fs = require('fs');

const config = JSON.parse(fs.readFileSync(__dirname + "/../config/config.json").toString()).backends.mqtt;

const client = mqtt.connect(config.ws, {
    username: config.username,
    password: config.password,
});

redis.connect().then(() => {
    redis.sendCommand([ 'config', 'set', 'notify-keyspace-events', 'Ex' ]).then(() => {
        redis.subscribe('__keyevent@0__:expired', (k, e) => {
            if (e == '__keyevent@0__:expired') {
                client.publish("redis/expire", JSON.stringify({ key: k }));
            }
        });
    });
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