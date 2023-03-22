#!/bin/env node

const app = require('express')();
const mqtt = require('mqtt');
const redis = require('redis')

var expired = redis.createClient();
expired.connect().then(() => {
    expired.sendCommand([ 'config', 'set', 'notify-keyspace-events', 'Ex']).then(() => {
        expired.subscribe('__keyevent@0__:expired', (k, e) => {
            if (e == '__keyevent@0__:expired') {
                console.log(k);
            }
        });
    });
});

const client = mqtt.connect("wss://tt.lanta.me/mqtt", {
    username: "rbt",
    password: "Hieth8ch",
});

client.subscribe("mqtt/demo");

client.on("message", console.log);

client.publish("mqtt/demo", "hello world!");

app.use(require('body-parser').urlencoded({ extended: true }));
app.listen(8082, '127.0.0.1');
