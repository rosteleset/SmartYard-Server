import fs from "fs";
import ejs from "ejs";
import express from "express";
import axios from "axios";

const app = express()
const API_ADDRESS = process.env.HOST || "localhost";
const PORT = process.env.PORT || 4291
const templateConfig = fs.readFileSync('./templates/akuvox_intercom_config_template.ejs', 'utf-8');

const parseSerialNumber = (serial) => {
    return serial.split(".")[0];
}

/**
 * get SIP credential for generate configuration
 * @param serial
 * @returns {Promise<any>}
 * return sipUsername, sipPassword, sipServer, stunServer, stunPort
 *
 */
const getConfig = async (serial) => {
    try {
        const response = await axios.get(`http://${API_ADDRESS}/internal/intercom/${serial}`);
        return response.data
    } catch (error) {
        console.error(error);
        throw error;
    }
}

// fake config
const getFakeConfig = serial => {
    return {
        sip: {
            enable: 1,
            label: "",
            displayName: "",
            userName: "",
            authName: "",
            pwd: "",
            transportType: 0, // 0 -udp, 1 - tcp
            sipServer: "rbt-demo.lanta.me",
            sipPort: "50142",
            stunEnable: 1,
            stunServer: "",
            stunPort: "",
        },
        provision: {
            url: "",
            mode: 1
        },
        sntp: {
            enable: 1,
            name: "Moscow",
            timeZone: "GMT+3:00"
        }

    }
}

// render client SIP intercom by serial
// TODO: add optional auth
app.get("/provision/:serial.cfg", async (req, res) => {
    try {
        const serial = parseSerialNumber(req.params.serial);
        console.log(`|| DEBUG || get config file > ${serial}.cfg`);
        const config = await  getConfig(serial);
        const renderedConfig = ejs.render(templateConfig,  config );
        res.status(200).send(renderedConfig);
    } catch (error) {
        console.error(error);
        res.status(500).send("Internal Server Error");
    }
});

app.listen(PORT, () => console.log(`Intercom provision server started on ${PORT}`));
