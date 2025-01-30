import axios from "axios";

export const getQtechMetrics = async (url, username, password) => {
    console.log(`${new Date().toLocaleString("RU")} | getQtechMetrics: ${url}`);
    const BASE_URL = url + '/api';
    const uptimePayload = {
        "target": "firmware",
        "action": "status",
        "data": {},
    }
    const sipStatusPayload = {
        "target": "sip",
        "action": "get",
        "data": {
            "AccountId": 0
        },
    }
    const instance = axios.create({
        baseURL: BASE_URL,
        timeout: 1000,
        auth: {
            username: username,
            password: password
        }
    });
    const parseSipStatus = (data) => {
        return data?.SipAccount?.AccountStatus === "2" ? 1 : 0
    }
    const parseUptime = (data) => {
        if (!data.UpTime){
            return 0
        }

        const upTime = data.UpTime
        // Регулярное выражение для поиска дней
        const dayMatch = upTime.match(/day:(\d+)/);

        // Регулярное выражение для поиска часов, минут и секунд, игнорируя лишние пробелы
        const timeMatch = upTime.match(/(\d+):\s*(\d+):\s*(\d+)/);

        if (dayMatch && timeMatch) {
            const days = parseInt(dayMatch[1], 10);
            const hours = parseInt(timeMatch[1].trim(), 10);
            const minutes = parseInt(timeMatch[2].trim(), 10);
            const seconds = parseInt(timeMatch[3].trim(), 10);

            return days * 86400 + hours * 3600 + minutes * 60 + seconds;
        } else {
            throw new Error(`Invalid UpTime format: ${upTime}`);
        }
    }

    try {
        const [ sysInfoData, sipStatusData,  ] = await Promise.all([
            instance.post('', JSON.stringify(uptimePayload)).then((res) =>  res.data.data),
            instance.post('', JSON.stringify(sipStatusPayload)).then((res) =>  res.data.data)
        ])

        const sipStatus = parseSipStatus(sipStatusData);
        const uptimeSeconds = parseUptime(sysInfoData);

        return { sipStatus, uptimeSeconds };
    } catch (err){
        console.error(`${new Date().toLocaleString("RU")} | Error fetching metrics from device ${url}:  ${err.message}`);
        throw new Error('Failed to fetch metrics from intercom');
    }
}