const http = require("http");

class WebHookService {
    constructor(unit, config) {
        this.unit = unit;
        this.config = config;
        this.server = http.createServer(this.requestListener.bind(this));
    }

    async requestListener(request, response) {
        if (request.url === this.config.apiEndpoint && request.method == "GET") {
            response.writeHead(202, {'Content-Type': 'application/json'})
            response.end(JSON.stringify({ message: "GET request received." }));
            await this.getEventHandler(request, response)
        }
        else if (request.url === this.config.apiEndpoint && request.method === "POST") {
            try {
                let data = '';
                request.on('data', (chunk) => {
                    data += chunk;
                });

                request.on('end', async () => {
                    const jsonData = JSON.parse(data);
                    await this.postEventHandler(request, jsonData);

                    response.writeHead(202, {'Content-Type': 'application/json'})
                    response.end(JSON.stringify({ message: "POST request received." }));
                })
            }
            catch (error) {
                console.error(error.message)
            }


        }
        else {
            response.writeHead(405, { 'Content-Type': 'application/json' });
            response.end(JSON.stringify({ message: "Method not allowed." }));

        }

    }

    async postEventHandler(request, data = null) {
    }

    async getEventHandler (request, data = null) {
    }

    start() {
        this.server.listen(this.config.port, () => {
            console.log(`${this.unit.toUpperCase()} Webhook server is listening on port ${this.config.port}`);
        });
    }
}

module.exports = {WebHookService}