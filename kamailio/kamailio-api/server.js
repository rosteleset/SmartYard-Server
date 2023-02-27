
import * as dotenv from 'dotenv'
dotenv.config()

import app from "./app/index.js";
const PORT = process.env.KAMAILIO_API_PORT || 50611;

app.listen(PORT, () => {
  console.log(`Kamailio api running on port ${PORT}`);
});
