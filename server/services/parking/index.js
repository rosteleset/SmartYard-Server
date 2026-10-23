// Run: node index.js [config file, config.json by default]

const fs = require("node:fs");
const path = require("node:path");
const geometry = require("./geometry.js");
const { createService } = require("./service.js");

const file = path.resolve(__dirname, process.argv[2] || "config.json");
const config = JSON.parse(fs.readFileSync(file, "utf8"));

const service = createService({ config, geometry });

service.start().then(address => {
    console.log(new Date().toISOString(), `parking occupancy service: ${file}, frames on ${address.address}:${address.port}`);
}).catch(e => {
    console.error(new Date().toISOString(), `can't start: ${e.message}`);
    process.exit(1);
});

for (const signal of [ "SIGINT", "SIGTERM" ]) {
    process.on(signal, () => service.stop().then(() => process.exit(0)));
}
