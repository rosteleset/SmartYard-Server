const { argv } = require('node:process');

require("dotenv").config({
    path: argv[2] ? argv[2] : `${process.env.NODE_ENV === "development" ? ".env_development" : ".env"}`,
});

const path = require('path')

//  Web server host and port
const PORT = process.env.APP_PORT || 8080;
const HOST = process.env.APP_HOST || "127.0.0.1";

// FCM
const CERT = path.join(__dirname, './assets/certificate-and-privatekey.pem');
const SERVICE_ACCOUNT = path.join(__dirname, './assets/pushServiceAccountKey.json');
const APP_PROJECT_NAME = process.env.APP_PROJECT_NAME || null;
const APP_BUNDLE_ID = process.env.APP_BUNDLE_ID || null;
const APP_USER_AGENT = process.env.APP_USER_AGENT || null;
const DB_NAME = process.env.APP_DATABASE_NAME || null;

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
    CERT,
    SERVICE_ACCOUNT,
    APP_PROJECT_NAME,
    APP_BUNDLE_ID,
    APP_USER_AGENT,
    DB_NAME,
    HUAWEI_CLIENT_ID,
    HUAWEI_CLIENT_SECRET,
    HUAWEI_PROJECT_ID,
    RUSTORE_PROJECT_ID,
    RUSTORE_TOKEN
}