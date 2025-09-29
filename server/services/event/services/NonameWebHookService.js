// example webhook service
import { WebHookService } from './index.js';

class NonameWebHookService extends WebHookService {
    async handlePostRequest(req, data) {
        console.log('handlePostRequest data >>>');

        const sourceIPAddress = req.connection.remoteAddress;
        console.log(sourceIPAddress);
    }

    async handleGetRequest(req, res) {
        console.log('handleGetRequest data >>>');

        const sourceIPAddress = req.connection.remoteAddress;
        console.log(sourceIPAddress);
    }
}

export { NonameWebHookService };
