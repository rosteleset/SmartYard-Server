const express = require("express");
const router = express.Router();
const subscribers = require("./subscribers");
const test =  require("./test")

// tests
//TODO: add kamailio health info
router.use("/subscribers", subscribers);
router.use("/test", test)

module.exports = router;
