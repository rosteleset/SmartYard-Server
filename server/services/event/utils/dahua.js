import http from 'node:http';
import https from 'node:https';
import { createHash, randomBytes } from 'node:crypto';

const hash = (algorithm, value) => createHash(algorithm).update(value).digest('hex');
const quote = value => `"${String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`;

/** RFC 7616: never downgrade a camera connection to Basic authentication. */
export function digestAuthorization(challenge, username, password, uri, cnonce = randomBytes(16).toString('hex')) {
    if (!/^Digest\s/i.test(challenge ?? '')) throw new Error('Dahua requires HTTP Digest authentication');
    const fields = {};
    for (const match of challenge.slice(7).matchAll(/([\w-]+)\s*=\s*(?:"((?:\\.|[^"\\])*)"|([^,\s]+))/g)) {
        fields[match[1].toLowerCase()] = (match[2] ?? match[3]).replace(/\\(.)/g, '$1');
    }
    if (!fields.realm || !fields.nonce) throw new Error('Incomplete Digest challenge');
    const algorithm = (fields.algorithm ?? 'MD5').toUpperCase();
    if (!['MD5', 'MD5-SESS', 'SHA-256', 'SHA-256-SESS'].includes(algorithm)) throw new Error('Unsupported Digest algorithm');
    const digest = algorithm.startsWith('MD5') ? 'md5' : 'sha256';
    const qop = fields.qop ? fields.qop.split(',').map(v => v.trim()).includes('auth') && 'auth' : null;
    if (fields.qop && !qop) throw new Error('Unsupported Digest qop');
    let ha1 = hash(digest, `${username}:${fields.realm}:${password}`);
    if (algorithm.endsWith('-SESS')) ha1 = hash(digest, `${ha1}:${fields.nonce}:${cnonce}`);
    const ha2 = hash(digest, `GET:${uri}`);
    const response = hash(digest, qop ? `${ha1}:${fields.nonce}:00000001:${cnonce}:auth:${ha2}` : `${ha1}:${fields.nonce}:${ha2}`);
    const parts = [
        `username=${quote(username)}`, `realm=${quote(fields.realm)}`, `nonce=${quote(fields.nonce)}`,
        `uri=${quote(uri)}`, `response=${quote(response)}`, `algorithm=${algorithm}`,
    ];
    if (fields.opaque) parts.push(`opaque=${quote(fields.opaque)}`);
    if (qop) parts.push('qop=auth', 'nc=00000001');
    if (qop || algorithm.endsWith('-SESS')) parts.push(`cnonce=${quote(cnonce)}`);
    return `Digest ${parts.join(', ')}`;
}

export function baseUrl(value) {
    let url;
    try { url = new URL(value); } catch { throw new Error('Invalid HTTP(S) base URL'); }
    if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password || url.search || url.hash) {
        throw new Error('Expected an HTTP(S) URL without credentials, query or fragment');
    }
    return url;
}

function request(url, { signal, headers = {}, timeoutMs = 45000, method = 'GET', body } = {}) {
    return new Promise((resolve, reject) => {
        const transport = url.protocol === 'https:' ? https : http;
        const req = transport.request(url, { method, headers, signal }, response => {
            clearTimeout(deadline);
            resolve(response);
        });
        // Include DNS/connect/TLS/header stalls, not just an established socket's idle timeout.
        const deadline = setTimeout(() => req.destroy(new Error('HTTP headers timeout')), Math.min(8000, timeoutMs));
        req.setTimeout(timeoutMs, () => req.destroy(new Error('HTTP idle timeout')));
        req.on('error', () => {
            clearTimeout(deadline);
            reject(new Error('HTTP connection failed or timed out'));
        });
        req.end(body);
    });
}

export async function cameraGet(camera, path, signal, timeoutMs = 45000) {
    const url = new URL(path, camera.url);
    let response = await request(url, { signal, timeoutMs });
    if (response.statusCode === 401) {
        const challenge = response.headers['www-authenticate'];
        response.destroy();
        const authorization = digestAuthorization(challenge, camera.username, camera.password, url.pathname + url.search);
        response = await request(url, { signal, timeoutMs, headers: { Authorization: authorization } });
    }
    if (response.statusCode !== 200) {
        const status = response.statusCode;
        response.destroy();
        const error = new Error(`Dahua HTTP ${status}`);
        if (status === 401 || status === 403) error.code = 'DAHUA_AUTH';
        throw error;
    }
    // Redirects are not followed, and TLS certificate validation stays enabled.
    return response;
}

export async function readMotionState(camera, signal, get = cameraGet) {
    const response = await get(camera, '/cgi-bin/eventManager.cgi?action=getEventIndexes&code=VideoMotion', signal, 5000);
    let body = '';
    try {
        for await (const chunk of response) {
            body += chunk.toString('utf8');
            if (body.length > 8192) throw new Error('Oversized Dahua motion state');
        }
    } finally {
        response.destroy();
    }
    const lines = body.trim() ? body.trim().split(/\r?\n/) : [];
    if (lines.some(line => !/^channels\[\d+\]=\d+$/.test(line.trim()))) throw new Error('Unsupported Dahua motion state response');
    return lines.some(line => /^channels\[\d+\]=0$/.test(line.trim()));
}

/** Incremental multipart/text line parser; a chunk is not necessarily a whole event. */
export class DahuaEventParser {
    buffer = '';
    push(chunk) {
        this.buffer += chunk.toString('utf8');
        const events = [];
        let newline;
        while ((newline = this.buffer.indexOf('\n')) !== -1) {
            if (newline > 8192) throw new Error('Oversized Dahua event line');
            const line = this.buffer.slice(0, newline).trim();
            this.buffer = this.buffer.slice(newline + 1);
            const match = /^Code=\s*VideoMotion;action=(Start|Stop);index=(\d+)(?:;.*)?$/.exec(line);
            if (match && Number(match[2]) === 0) events.push(match[1] === 'Start');
        }
        if (this.buffer.length > 8192) throw new Error('Oversized Dahua event buffer');
        return events;
    }
}

export function motionSender(internalUrl) {
    const base = baseUrl(internalUrl);
    const url = new URL(base.toString().replace(/\/$/, '') + '/actions/motionDetection');
    return async (event, signal) => {
        const body = JSON.stringify(event);
        const response = await request(url, {
            method: 'POST', body, signal, timeoutMs: 5000,
            headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(body) },
        });
        const status = response.statusCode;
        response.destroy();
        if (status < 200 || status >= 300) throw new Error(`RBT motion API HTTP ${status}`);
    };
}
