const express = require("express");
const { json } = require("express");
const app = express();
const router = require("./routes/v1");


app.disable("x-powered-by");

app.use(json());

app.use(function (req, res, next) {
  const ipAddress = req.ip.split(":")[req.ip.split(":").length - 1];
  if (ipAddress !== process.env.ALLOWED_HOST) {
    return res.sendStatus(401);
  }
  next();
});

app.use("/api/v1", router);

app.use("*", (req, res) => res.sendStatus(403));

module.exports = app;
