const express = require("express");
const router = express.Router();

const {
    getSubscribers,
    getSubscriberByName,
    createSubscriber,
    updateSubscriber,
    deleteSubscriber,
    test,
  } = require("../queries");


router.get("/subscribers", getSubscribers);

router.get("/subscribers/:userName", getSubscriberByName);

router.post("/subscribers", createSubscriber);

router.put("/subscribers", updateSubscriber);

router.delete("/subscribers/:userName", deleteSubscriber);

//TODO: add kamailio health info
router.get("/health", (request, response) => {
  response.json({ info: "KAMAILIO is working ☎️" });
});

module.exports = router;
