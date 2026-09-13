// Real Chromium with synthetic camera/microphone. --call rings the configured
// selected apartment; use only when its resident has agreed to receive the call.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const args = process.argv.slice(2);
const url = args.find(x => x.startsWith('https://'));
const output = process.env.VI_BROWSER_OUTPUT || '/tmp/virtual-intercom-browser';
if (!url) throw new Error('Pass the complete panel URL');
fs.mkdirSync(output, { recursive: true });
(async () => {
    const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        headless: true, args: ['--use-fake-device-for-media-stream', '--use-fake-ui-for-media-stream', '--autoplay-policy=no-user-gesture-required'] });
    try {
        const context = await browser.newContext({ viewport: { width: 1200, height: 950 }, permissions: ['camera', 'microphone'] });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        page.on('response', response => { if (response.status() >= 400) console.log('HTTP_ERROR', response.status(), new URL(response.url()).pathname); });
        await page.addInitScript(({ relay }) => {
            window.__viPeers = [];
            const Original = window.RTCPeerConnection;
            window.RTCPeerConnection = class extends Original {
                constructor(config, ...rest) {
                    super(relay ? { ...config, iceTransportPolicy: 'relay' } : config, ...rest);
                    this.__createdAt = performance.now(); window.__viPeers.push(this);
                    this.__events = [];
                    for (const event of ['icegatheringstatechange','signalingstatechange','icecandidateerror','icecandidate']) {
                        this.addEventListener(event, e => this.__events.push({event, at:Math.round(performance.now()-this.__createdAt), state:this.iceGatheringState, candidateType:e.candidate?.type, errorCode:e.errorCode}));
                    }
                }
                async createOffer(...args) { this.__events.push({event:'createOffer'}); const r = await super.createOffer(...args); this.__events.push({event:'offered'}); return r; }
                async setLocalDescription(...args) { this.__events.push({event:'setLocalDescription'}); const r = await super.setLocalDescription(...args); this.__events.push({event:'localDescriptionSet'}); return r; }
                async setRemoteDescription(...args) { this.__events.push({event:'setRemoteDescription'}); const r = await super.setRemoteDescription(...args); this.__events.push({event:'remoteDescriptionSet'}); return r;

                }
            };
        }, { relay: args.includes('--relay') });
        await page.goto(url, { waitUntil: 'networkidle' });
        await page.getByRole('tab', { name: 'Выбрать из списка' }).click();
        await page.getByRole('button', { name: /Квартира 1/ }).click();
        await page.getByRole('tab', { name: 'Набрать номер' }).click();
        await page.screenshot({ path: path.join(output, 'desktop.png'), fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
        if (overflow) throw new Error('Horizontal overflow on mobile');
        await page.screenshot({ path: path.join(output, 'mobile.png'), fullPage: true });
        console.log(JSON.stringify({ stage: 'layout', title: await page.title(), status: await page.locator('#status').innerText(), errors }));
        if (!args.includes('--call')) return;
        await page.setViewportSize({ width: 1200, height: 950 });
        const clickedAt = new Date().toISOString();
        const clickedTime = Date.now();
        console.log(JSON.stringify({stage: 'calling', clickedAt}));
        await page.getByRole('button', { name: 'Позвонить', exact: true }).click();
        const records = [];
        for (let i = 0; i < 50; i++) {
            await page.waitForTimeout(2000);
            const snapshot = await page.evaluate(async () => {
                const peers = [];
                for (const pc of window.__viPeers) {
                    const stats = await pc.getStats();
                    const rtp = [], candidates = [], sources = [], transports = [];
                    stats.forEach(s => {
                        if(s.type === 'media-source') sources.push({kind:s.kind,frames:s.frames,framesPerSecond:s.framesPerSecond,width:s.width,height:s.height});
                        if(s.type === 'transport') transports.push({dtlsState:s.dtlsState,iceState:s.iceState,bytesSent:s.bytesSent,bytesReceived:s.bytesReceived});
                        if (['outbound-rtp', 'inbound-rtp'].includes(s.type)) rtp.push({ type: s.type, kind: s.kind,
                            packetsSent: s.packetsSent, packetsReceived: s.packetsReceived, framesEncoded: s.framesEncoded,
                            framesDecoded: s.framesDecoded, bytesSent: s.bytesSent, bytesReceived: s.bytesReceived,
                            codec: stats.get(s.codecId)?.mimeType, targetBitrate:s.targetBitrate, totalEncodeTime:s.totalEncodeTime, totalPacketSendDelay:s.totalPacketSendDelay, qualityLimitationReason:s.qualityLimitationReason, keyFramesEncoded:s.keyFramesEncoded, encoderImplementation:s.encoderImplementation, packetsLost:s.packetsLost, jitter:s.jitter });
                        if (s.type === 'candidate-pair' && s.state === 'succeeded' && s.nominated) candidates.push({
                            availableOutgoingBitrate:s.availableOutgoingBitrate,currentRoundTripTime:s.currentRoundTripTime, localType: stats.get(s.localCandidateId)?.candidateType, remoteType: stats.get(s.remoteCandidateId)?.candidateType });
                    });
                    peers.push({ connection: pc.connectionState, ice: pc.iceConnectionState, gathering:pc.iceGatheringState, signaling:pc.signalingState, events:pc.__events, sources,transports, elapsedMs:Math.round(performance.now()-pc.__createdAt), sdp: [pc.localDescription?.sdp,pc.remoteDescription?.sdp].map(s => s?.split('\r\n').filter(l => /^(m=|b=|a=(rtpmap|fmtp|rtcp-fb|sendrecv|sendonly|recvonly|extmap|setup))/.test(l))), transceivers: pc.getTransceivers().map(t => ({kind:t.sender.track?.kind,direction:t.direction})), rtp, candidates });
                }
                return { status: document.getElementById('status').textContent, busy: document.body.classList.contains('busy'), peers };
            });
            snapshot.sinceClickMs = Date.now() - clickedTime;
            records.push(snapshot);
            console.log(JSON.stringify({...snapshot, peers:snapshot.peers.map(({events,sdp,...p})=>p)}));
            fs.writeFileSync(path.join(output, 'call.json'), JSON.stringify({ clickedAt, records, errors }, null, 2));
            if (i === 5 || !snapshot.busy) await page.screenshot({ path: path.join(output, 'call.png'), fullPage: true });
            if (!snapshot.busy) break;
        }
        if (await page.locator('#hangup').isVisible()) await page.locator('#hangup').click();
        console.log(JSON.stringify({ stage: 'finished', errors }));
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
