#!/bin/env node

const app = require('express')();
const mqtt = require('mqtt');
const redis = require('redis')
const fs = require('fs');

var expired = redis.createClient();
expired.connect().then(() => {
    expired.sendCommand([ 'config', 'set', 'notify-keyspace-events', 'Ex' ]).then(() => {
        expired.subscribe('__keyevent@0__:expired', (k, e) => {
            if (e == '__keyevent@0__:expired') {
                console.log(k);
            }
        });
    });
});

let mqtt_config = JSON.parse(fs.readFileSync(__dirname + "/../config/config.json").toString()).backends.mqtt;

const client = mqtt.connect(mqtt_config.ws, {
    username: mqtt_config.username,
    password: mqtt_config.password,
});

client.subscribe("mqtt/demo");

client.on("message", console.log);

client.publish("mqtt/demo", "hello world!");

app.use(require('body-parser').urlencoded({ extended: true }));
app.listen(8082, '127.0.0.1');
