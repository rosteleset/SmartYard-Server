import axios from "axios";

//TODO: implement me!
export const getRubetekMetrics = async (url, username = 'admin', password) => {
    console.log(`${new Date().toLocaleString("RU")} | getRubetekMetrics: ${url}`);
    const BASE_URL = url + '/cgi-bin';
    const PATH_SIP_STATUS = '/sip_cgi?action=regstatus&AccountReg';
    const PATH_SYSINFO = '/systeminfo_cgi?action=get';

    const instance = axios.create({
        baseURL: BASE_URL,
        timeout: 1000,
        auth: {
            username: username,
            password: password
        }
    });

    /**
     * Extract value of AccountReg1
     * @param data
     * @returns {number|number}
     */
    const parseSipStatus = (data) => {
        const match = data.match(/AccountReg1=(\d+)/);
        return match ? parseInt(match[1], 10) : 0;
    };

    /**
     * Extract value of UpTime and convert to seconds
     * @param data
     * @returns {number}
     * @example "UpTime=20:22:31", "UpTime=11.18:44:44"
     */
    const parseUptimeMatch = (data) => {
        const match = data.match(/UpTime=(?<days>\d+\.)?(?<hours>\d+\:)(?<minutes>\d+\:)(?<seconds>\d+)/);
        if (!match || !match.groups) {
            return 0;
        }
        const { days = 0, hours, minutes, seconds } = match.groups;
        return (parseInt(days, 10) * 24 * 3600)
            + (parseInt(hours, 10) * 3600)
            + (parseInt(minutes, 10) * 60)
            + parseInt(seconds, 10);
    }

    try {
        const [sipStatusData, sysInfoData] = await Promise.all([
            instance.get(PATH_SIP_STATUS).then(({data}) => data),
            instance.get(PATH_SYSINFO).then(({data}) => data)
        ]);

        const sipStatus = parseSipStatus(sipStatusData);
        const uptimeSeconds = parseUptimeMatch(sysInfoData);

        return { sipStatus, uptimeSeconds };
    } catch (err){
        console.error(`${new Date().toLocaleString("RU")} | Error fetching metrics from device ${url}:  ${err.message}`);
        throw new Error('Failed to fetch metrics from intercom');
    }
}
