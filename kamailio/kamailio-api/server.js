require("dotenv").config();
const app = require("./app");
const PORT = process.env.KAMAILIO_API_PORT || 50611;

app.listen(PORT, () => {
  console.log(`Kamailio api running on port ${PORT}`);
});
