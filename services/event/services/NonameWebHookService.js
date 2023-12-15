// example webhook service
const { WebHookService } = require("./base/WebHookService");

class NonameWebHookService  extends  WebHookService{
    constructor(unit, config) {
        super(unit, config)
    }

    async postEventHandler(req, data) {
        console.log("postEventHandler data >>>");

        const sourceIPAddress = req.connection.remoteAddress
        console.log(sourceIPAddress)


    }

    async getEventHandler (req, data){
        console.log("getEventHandler data >>>");

        const sourceIPAddress = req.connection.remoteAddress
        console.log(sourceIPAddress)
    }


}

module.exports = { NonameWebHookService }
