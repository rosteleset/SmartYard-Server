const { argv } = require('node:process');

let file = parseInt(argv[2]) ? (".env" + argv[2].toString()) : ".env";

require("dotenv").config({
    path: file,
});

console.log("using " + file + " config file");

const path = require('path')

//  Web server host and port
const PORT = process.env.APP_PORT || 8080;
const HOST = process.env.APP_HOST || "127.0.0.1";

// COMMON

// FCM
const FCM_PROJECT_NAME = process.env.FCM_PROJECT_NAME || null;
const FCM_DATABASE_NAME = process.env.FCM_DATABASE_NAME || null;
const FCM_SERVICE_ACCOUNT = process.env.FCM_SERVICE_ACCOUNT ? path.join(__dirname, './assets/' + process.env.FCM_SERVICE_ACCOUNT) : path.join(__dirname, './assets/pushServiceAccountKey.json');

// APN
const APN_BUNDLE_ID = process.env.APN_BUNDLE_ID || null;
const APN_USER_AGENT = process.env.APN_USER_AGENT || null;
const APN_CERT = process.env.APN_CERT ? path.join(__dirname, './assets/' + process.env.APN_CERT) : path.join(__dirname, './assets/certificate-and-privatekey.pem');

// HCM
const HUAWEI_CLIENT_ID = process.env.HUAWEI_CLIENT_ID || null;
const HUAWEI_CLIENT_SECRET = process.env.HUAWEI_CLIENT_SECRET || null;
const HUAWEI_PROJECT_ID = process.env.HUAWEI_PROJECT_ID || null;

// RUSTORE
const RUSTORE_PROJECT_ID = process.env.RUSTORE_PROJECT_ID || null;
const RUSTORE_TOKEN = process.env.RUSTORE_TOKEN || null;

module.exports = {
    PORT,
    HOST,

    FCM_PROJECT_NAME,
    FCM_DATABASE_NAME,
    FCM_SERVICE_ACCOUNT,

    APN_BUNDLE_ID,
    APN_USER_AGENT,
    APN_CERT,

    HUAWEI_CLIENT_ID,
    HUAWEI_CLIENT_SECRET,
    HUAWEI_PROJECT_ID,

    RUSTORE_PROJECT_ID,
    RUSTORE_TOKEN
}

console.log(module.exports);
