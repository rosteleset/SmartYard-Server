require("dotenv").config();
const app = require("./app");
const port = process.env.KAMAILIO_API_PORT || 50611;

app.listen(port, () => {
  console.log(`Kamailio api running on port ${port} `);
});
