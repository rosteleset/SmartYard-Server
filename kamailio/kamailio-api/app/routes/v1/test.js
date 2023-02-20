const express = require("express");
const router = express.Router();

const { test } = require("../../queries");
const apiCall = require("../../api")

// tests
//TODO: add kamailio health info
router.get("/", (request, response) => {
  response.json({ info: "KAMAILIO is working ☎️" });
});

router.get('/info', async (req,res)=> {
  res.json(await apiCall({method:"core.uptime"}).then(({data})=> data))
})

router.get('/ul', async (req,res)=> {
  try {
    const {username} = req.body;
    res.json(await apiCall({method:"ul.lookup", params:["location", "4000000002@umbrella.lanta.me"]}).then(({data})=> data))

  } catch (error) {
    
  }
})

module.exports = router;
