const {WebHookService} = require("./WebHookService");
const {parseString} = require('xml2js');

class OmnyWebHookService extends WebHookService {
    constructor(unit, config) {
        super(unit, config)
    }

    async requestListener(request, response) {
        let data = '';
        request.on('data', (chunk) => {
            data += chunk.toString();
        });

        request.on('end', async () => {
            parseString(data, (error, result) => {
                if (error) {
                    console.error('Error parsing XML')
                    res.writeHead(500, {'Content-Type': 'text/plain'});
                    res.end('Error');
                }
                console.table(result)
            })
        })
    }

    async postEventHandler(req, data) {

    }

    async getEventHandler(req, data) {

    }


}

module.exports = {OmnyWebHookService}
