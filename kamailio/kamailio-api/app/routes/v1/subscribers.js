const express = require("express");
const router = express.Router();

const {
  getSubscribers,
  getSubscriberByName,
  createSubscriber,
  updateSubscriber,
  deleteSubscriber,
} = require("../../queries");

router.get("/", getSubscribers);

router.get("/:userName", getSubscriberByName);

router.post("/", createSubscriber);

router.put("/", updateSubscriber);

router.delete("/:userName", deleteSubscriber);

module.exports = router;
