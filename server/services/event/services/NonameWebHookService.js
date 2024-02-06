// example webhook service
import { WebHookService } from "./index.js";

class NonameWebHookService extends WebHookService {
    constructor(unit, config) {
        super(unit, config)
    }

    async postEventHandler(req, data) {
        console.log("postEventHandler data >>>");

        const sourceIPAddress = req.connection.remoteAddress
        console.log(sourceIPAddress)


    }

    async getEventHandler(req, data) {
        console.log("getEventHandler data >>>");

        const sourceIPAddress = req.connection.remoteAddress
        console.log(sourceIPAddress)
    }


}

export { NonameWebHookService }
