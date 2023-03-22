#!/bin/env node

const app = require('express')();
const mqtt = require('mqtt');
const redis = require('redis').createClient();
const fs = require('fs');

redis.connect().then(() => {
    redis.sendCommand([ 'config', 'set', 'notify-keyspace-events', 'Ex' ]).then(() => {
        redis.subscribe('__keyevent@0__:expired', (k, e) => {
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

client.publish("cs/cell", JSON.stringify({
    action: "claim",
    sheet: "sheet",
    date: "date",
    col: "col",
    row: "row",
    uid: "uid",
    login: "login",
}));

app.use(require('body-parser').urlencoded({ extended: true }));
app.listen(8082, '127.0.0.1');
