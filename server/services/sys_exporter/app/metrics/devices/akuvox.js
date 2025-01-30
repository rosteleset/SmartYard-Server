import DigestFetch from "digest-fetch";

/**
 * get Akuvox intercom metrics
 * @param url
 * @param username
 * @param password
 * @returns {Promise<{sipStatus: (number), uptimeSeconds: *}>}
 */
export const getAkuvoxMetrics = async (url, username, password) => {
    console.log(`${new Date().toLocaleString("RU")} | getBewardMetrics: ${url}`);
    const digestClient = new DigestFetch(username, password);
    const BASE_URL = url + '/api';
    const statusPayload = {
        target: 'system',
        action: 'status'
    };
    const infoPayload = {
        target: 'system',
        action: 'info'
    };

    class DigestClient {
        constructor(client, baseUrl) {
            this.client = client;
            this.baseUrl = baseUrl;
        }

        async post(endpoint, payload, timeout = 5000) {
            return new Promise((resolve, reject) => {
                const timer = setTimeout(() => {
                    reject(new Error(`Request timeout: ${timeout} ms`));
                }, timeout);

                this.client.fetch(this.baseUrl + endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(response => {
                        clearTimeout(timer);
                        if (!response.ok) {
                            reject(new Error(`HTTP error! status: ${response.status}`));
                        } else {
                            resolve(response.json());
                        }
                    })
                    .catch(err => {
                        clearTimeout(timer);
                        reject(err);
                    });
            });
        }
    }

    const instance = new DigestClient(digestClient, BASE_URL);

    try {
        const [statusResponse, infoResponse] = await Promise.all([
            instance.post('', statusPayload).then(({data}) => data),
            instance.post('', infoPayload).then(({data}) => data)
        ]);

        const parseUptime = (data) => {
            return data.UpTime ?? 0;
        };

        const parseSipStatus = (data) => {
            return data.Account1.Status === "2" ? 1 : 0;
        };

        const sipStatus = parseSipStatus(infoResponse);
        const uptimeSeconds = parseUptime(statusResponse);

        return { sipStatus, uptimeSeconds };
    } catch (err) {
        console.error(`${new Date().toLocaleString("RU")} | Error fetching metrics from device ${url}: ${err.message}`);
        throw new Error('Failed to fetch metrics from intercom');
    }
};
