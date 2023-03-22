#!/bin/env node

const app = require('express')();
const mqtt = require('mqtt');

const client = mqtt.connect("wss://tt.lanta.me/mqtt", {
    username: "rbt",
    password: "Hieth8ch",
});

client.subscribe("mqtt/demo");

client.on("message", console.log);

client.publish("mqtt/demo", "hello world!");

app.use(require('body-parser').urlencoded({ extended: true }));
app.listen(8082, '127.0.0.1');
