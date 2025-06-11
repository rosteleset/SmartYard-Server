import { SERVICE_BROVOTECH, SERVICE_UFANET } from '../constants.js';
import { getTimestamp } from './index.js';

const parseSyslogMessage = (str, unit) => {
    if (!str) {
        return false;
    }

    str = str.trim();

    if (unit === SERVICE_UFANET) {
        const index = str.indexOf(': ');
        const message = str.substring(index + 2);

        return {
            hostname: null,
            message: message,
        };
    }

    if (unit === SERVICE_BROVOTECH) {
        return {
            hostname: null,
            message: str,
        };
    }

    // Check if the message follows the RFC 5424 format
    const regexIETF = /<(?<priority>\d{1,3})>(?<version>\d+) (?<timestamp>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?\w?(?:[+-]\d{2}:\d{2})?) (?<hostname>\S+) (?<app>\S+) (?<pid>\S+) (?<msg_id>\S+)\s-\s(?<message>.*)$/;

    // BSD RFC 3164 format
    // const regexBSB = /<(?<priority>\d{1,3})>(?<timestamp>\w+\s+\d{1,2}\s\d{2}:\d{2}:\d{2})\s(?<host>\S+)?\s(?<app>[\w.-]+)\s(?<pid>\S+):\s(?<message>.*)$/;
    const regexBSB = /<(?<priority>\d{1,3})>(?<timestamp>\w+\s+\d{1,2}\s\d{2}:\d{2}:\d{2})\s(?<host>\S+)?\s(?<app>[\w\s.]+)\s(?<pid>\S+):\s(?<message>.*)$/;

    // ISComX1 rev.5
    // const regexSokolPlus = /<(?<priority>\d{1,3})>(?<timestamp>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}\+\d{2}:\d{2}) (?<hostname>\S+) (?<app>\w+)\[(?<pid>\d+)]: (?<message>.*)$/;

    // Rubetek
    const regexRubetek = /<(?<priority>\d{1,3})>(?<timestamp>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}) (?<hostname>\S+) (?<app>\S+) (?<message>.*)$/;

    const partsIETF = regexIETF.exec(str);
    const partsBSD = regexBSB.exec(str);
    // const partsSokolPlus = regexSokolPlus.exec(str);
    const partsRubetek = regexRubetek.exec(str);

    if (partsIETF) {
        const [, priority, version, timestamp, hostname, app, pid, msg_id, message] = partsIETF;
        return {
            format: 'RFC5424',
            priority: Number(priority),
            version: Number(version),
            timestamp: getTimestamp(new Date(timestamp)),
            hostname,
            app,
            pid,
            message,
        };
    } else if (partsBSD) {
        const [, priority, timestamp, host, app, pid, message] = partsBSD;
        return {
            format: 'BSD',
            priority: Number(priority),
            hostname: host,
            pid,
            app,
            message,
        };
        // } else if (partsSokolPlus) {
        //     const [, priority, timestamp, hostname, app, pid, message] = partsSokolPlus;
        //     return {
        //         format: 'SokolPlus',
        //         priority: Number(priority),
        //         timestamp: getTimestamp(new Date(timestamp)),
        //         hostname,
        //         app,
        //         pid,
        //         message,
        //     };
    } else if (partsRubetek) {
        const [, priority, timestamp, hostname, app, messageRaw] = partsRubetek;

        // Cut message prefix for new firmwares (2025.02 and newer)
        const message = messageRaw.replace('[Intercom]:', '').trim();

        return {
            format: 'Rubetek',
            priority: Number(priority),
            timestamp: getTimestamp(new Date(timestamp)),
            hostname,
            app,
            message,
        };
    }

    return false;
};

export { parseSyslogMessage };
