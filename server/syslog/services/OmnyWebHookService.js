const {WebHookService} = require("./WebHookService");
const {parseString} = require('xml2js');

class OmnyWebHookService extends WebHookService {
    constructor(unit, config) {
        super(unit, config)
    }

    async requestListener(request, response) {
        if (request.url === this.config?.apiEndpoint && request.method === "POST"){
            let data = '';
            request.on('data', (chunk) => {
                data += chunk.toString();
            });

            request.on('end', async () => {
                if (!data) {
                    response.writeHead(400, {'Content-Type': 'application/json'})
                    response.end(JSON.stringify({ message: "Request body is empty." }));

                    // TODO: make logger
                    console.error(`${new Date().toLocaleString("RU")} || ${request.connection.remoteAddress} || Request body is empty.`)
                    return;
                }

                parseString(data, async (error, result) => {
                    if (error) {
                        console.error("Error parsing XML:", error.message)
                    }

                    console.table(request)
                    await this.parsedDataHandler(result);

                    response.writeHead(202, {'Content-Type': 'application/json'})
                    response.end(JSON.stringify({ message: "Webhook received and processed." }));
                })

            })
        }
        else {
            response.writeHead(405, { 'Content-Type': 'application/json' });
            response.end(JSON.stringify({ message: "Method not allowed." }));
        }

    }

    async postEventHandler(req, data) {
    }

    async getEventHandler(req, data) {
    }

    async parsedDataHandler(parsedData) {
        // TODO: - add check event param handler
        const {event: {title, time, status}} = parsedData;

        // TODO : add event handlers
        // motion detect
        if (title === "motion_dect" ) {

        }

       // Human detect
        if (title === "human_dect" ) {

        }

        // Crossing line
        if (title === "crossing_dect" ) {

        }
    }


}

module.exports = {OmnyWebHookService}
