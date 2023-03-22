#!/bin/env node

const express = require('express');
const app = express();
const mqtt = require('mqtt');
const redis = require('redis').createClient();
const fs = require('fs');

const mqtt_config = JSON.parse(fs.readFileSync(__dirname + "/../config/config.json").toString()).backends.mqtt;

const client = mqtt.connect(mqtt_config.ws, {
    username: mqtt_config.username,
    password: mqtt_config.password,
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
    res.status(200).end();
});

app.use(require('body-parser').urlencoded({ extended: true }));
app.listen(8082, '127.0.0.1');
