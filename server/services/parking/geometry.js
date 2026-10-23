// the parking geometry of the client, loaded as is: one implementation of the layout for the form and the service

const fs = require("node:fs");
const path = require("node:path");
const vm = require("node:vm");

const file = path.join(__dirname, "../../../client/js/parkingGeometry.js");
const context = {};

vm.runInNewContext(fs.readFileSync(file, "utf8"), context, { filename: file });

module.exports = context.parkingGeometry;
