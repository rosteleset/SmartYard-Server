require("dotenv").config();
const express = require("express");
const { json } = require("express");
const router = require("./router");

const app = express();
const port = process.env.KAMAILIO_API_PORT || 50611;

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

app.listen(port, () => {
  console.log(`Kamailio api running on port ${port} `);
});
