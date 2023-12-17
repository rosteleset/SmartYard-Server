import { parseString } from 'xml2js';
import { WebHookService } from "./index.js";
import { API, getTimestamp } from "../utils/index.js";

class OmnyWebHookService extends WebHookService {
    constructor(unit, config) {
        super(unit, config)
    }

    async requestListener(request, response) {
        const now = getTimestamp(new Date());
        const sourceIP = request.connection.remoteAddress.split("::ffff:")[1];
        if (request.url === this.config?.apiEndpoint && request.method === "POST") {
            let data = '';
            request.on('data', (chunk) => {
                data += chunk.toString();
            });

            request.on('end', async () => {
                if (!data) {
                    response.writeHead(400, {'Content-Type': 'application/json'});
                    response.end(JSON.stringify({message: "Request body is empty."}));

                    // TODO: make logger
                    console.error(`${new Date().toLocaleString("RU")} || ${request.connection.remoteAddress} || Request body is empty.`)
                    return;
                }

                parseString(data, async (error, result) => {
                    if (error) console.error("Error parsing XML:", error.message);
                    await this.parsedDataHandler({sourceIP, now, ...result});
                    response.writeHead(202, {'Content-Type': 'application/json'})
                    response.end(JSON.stringify({message: "Webhook received and processed."}));
                })

            })
        } else {
            response.writeHead(405, {'Content-Type': 'application/json'});
            response.end(JSON.stringify({message: "Method not allowed."}));
        }

    }

    async postEventHandler(req, data) {
    }

    async getEventHandler(req, data) {
    }

    async parsedDataHandler(parsedData) {
        try {
            const {sourceIP, now, event: {title: [title], time: [time], status: [status]}} = parsedData;
            // TODO : add event handlers

            // motion detect logic
            if (title === "motion_dect") {
                if (status === "start") {
                    await API.motionDetection({date: now, ip: sourceIP, motionActive: true});
                } else if (status === "end") {
                    await API.motionDetection({date: now, ip: sourceIP, motionActive: false});
                }
            }

            // Human detect logic
            if (title === "human_dect") {
            }

            // Crossing line logic
            if (title === "crossing_dect") {
            }
        } catch (error) {
            console.error()
        }

    }


}

export { OmnyWebHookService }
