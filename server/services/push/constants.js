const { argv } = require('node:process');

require("dotenv").config({
    path: argv[2] ? argv[2] : ".env",
});

console.log("using " + (argv[2] ? argv[2] : ".env") + " config file");

const path = require('path')

//  Web server host and port
const PORT = process.env.APP_PORT || 8080;
const HOST = process.env.APP_HOST || "127.0.0.1";

// FCM
const FCM_SERVICE_ACCOUNT = process.env.FCM_SERVICE_ACCOUNT ? path.join(__dirname, './assets/' + process.env.FCM_SERVICE_ACCOUNT) : path.join(__dirname, './assets/pushServiceAccountKey.json');
const FCM_APP_PROJECT_NAME = process.env.APP_PROJECT_NAME || null;
const FCM_APP_BUNDLE_ID = process.env.APP_BUNDLE_ID || null;
const FCM_APP_USER_AGENT = process.env.APP_USER_AGENT || null;
const FCM_DB_NAME = process.env.APP_DATABASE_NAME || null;

// APN
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

    FCM_SERVICE_ACCOUNT,
    FCM_APP_PROJECT_NAME,
    FCM_APP_BUNDLE_ID,
    FCM_APP_USER_AGENT,
    FCM_DB_NAME,

    APN_CERT,

    HUAWEI_CLIENT_ID,
    HUAWEI_CLIENT_SECRET,
    HUAWEI_PROJECT_ID,

    RUSTORE_PROJECT_ID,
    RUSTORE_TOKEN
}

console.log(module.exports);