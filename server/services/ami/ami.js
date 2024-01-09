#!/bin/env node

const fs = require('fs');
const ami = new require('asterisk-manager')('5038', '127.0.0.1', 'asterisk', '881d6256664648e0ebe1ed0e9b1340f2', true);

console.log(JSON.parse(fs.readFileSync("../config/config.json")));
