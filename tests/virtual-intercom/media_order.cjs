// Real JsSIP and RTCPeerConnection; HTTP and WebSocket stay inside Playwright.
// Reproduce Safari's variable MediaStream.getTracks() order without resident calls.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');

(async () => {
  const browser = await chromium.launch({headless:true,
    executablePath:process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    args:['--use-fake-device-for-media-stream','--use-fake-ui-for-media-stream']});
  try {
    const context = await browser.newContext({permissions:['camera','microphone']});
    const page = await context.newPage(), errors = [], offers = [];
    page.on('pageerror', e => errors.push(e.message));
    await page.addInitScript(() => {
      const acquire = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);
      const NativePC = window.RTCPeerConnection;
      window.RTCPeerConnection = class extends NativePC {
        constructor(...args) { super(...args); window.testPC = this; }
      };
      window.testCaptureOrder = ['video','audio'];
      const getTracks = MediaStream.prototype.getTracks;
      MediaStream.prototype.getTracks = function() {
        return getTracks.call(this).sort((a,b) =>
          window.testCaptureOrder.indexOf(a.kind) - window.testCaptureOrder.indexOf(b.kind));
      };
      navigator.mediaDevices.getUserMedia = async options => {
        const stream = await acquire(options);
        window.testMedia = stream;
        return stream;
      };
    });
    let sessionNumber = 0;
    await page.route('**/*', route => {
      const u = new URL(route.request().url());
      assert.equal(u.hostname,'virtual-intercom.test','Unexpected external HTTP request');
      if (u.pathname === '/v/fixturePanel') return route.fulfill({path:path.join(root,'static/virtual-intercom/index.html')});
      if (u.pathname.startsWith('/virtual-intercom/assets/')) return route.fulfill({path:path.join(root,'static/virtual-intercom',u.pathname.split('/assets/')[1])});
      if (u.pathname.endsWith('/jssip.min.js')) return route.fulfill({path:path.join(root,'client/js/jssip.min.js')});
      const reply = data => route.fulfill({json:{ok:true,...data}});
      if (u.pathname.endsWith('/api/panel')) return reply({title:'Fixture',subtitle:'',listEnabled:false,flats:[{id:1,number:'1'}]});
      if (u.pathname.endsWith('/api/session')) return reply({id:'fixture-'+ ++sessionNumber,token:'fixture',sip:{username:'fixture',password:'fixture',domain:u.hostname,ws:'wss://virtual-intercom.test/wss',iceServers:[]}});
      if (u.pathname.endsWith('/api/status')) return reply({status:'ringing',doorStatus:'idle'});
      if (/\/api\/(frame|cancel)$/.test(u.pathname)) return reply({});
      if (u.pathname === '/favicon.ico') return route.fulfill({status:204});
      throw Error('Unexpected route '+u.pathname);
    });
    await page.routeWebSocket('**/wss', ws => {
      ws.onMessage(message => {
        if (typeof message === 'string' && message.startsWith('INVITE ')) offers.push(message.split('\r\n\r\n')[1]);
      });
    });
    await page.goto('https://virtual-intercom.test/v/fixturePanel');
    await page.locator('#entrance-title').getByText('Fixture').waitFor();
    await page.locator('#apartment').fill('1');
    for (const order of [['video','audio'],['audio','video'],['video','audio']]) {
      await page.evaluate(order => { window.testCaptureOrder = order; },order);
      const next = offers.length + 1;
      await page.locator('#call').click();
      await page.waitForFunction(() => window.testPC?.localDescription?.type === 'offer');
      await page.waitForTimeout(100);
      const deadline = Date.now()+10000;
      while (offers.length < next && Date.now()<deadline) await page.waitForTimeout(100);
      assert.equal(offers.length,next,'JsSIP did not send its offer');
      const kinds = [...offers.at(-1).matchAll(/^m=(\w+) /gm)].map(m=>m[1]);
      assert.deepEqual(kinds,['audio','video'],`Incorrect SDP order for capture ${order}`);
      const state = await page.evaluate(() => {
        window.testOutgoing = testPC.getSenders().map(s=>s.track);
        const audio = testOutgoing.find(t=>t.kind==='audio'), video = testOutgoing.find(t=>t.kind==='video');
        return {kinds:testPC.getTransceivers().map(t=>t.sender.track?.kind),
          originalAudio:audio===testMedia.getAudioTracks()[0],croppedVideo:video!==testMedia.getVideoTracks()[0],
          videoSize:[video.getSettings().width,video.getSettings().height],
          tracks:testMedia.getTracks().map(t=>t.kind),
          outgoingOrder:new MediaStream(testOutgoing).getTracks().map(t=>t.kind)};
      });
      assert.deepEqual(state.kinds,['audio','video'],'Extra or empty transceiver');
      assert(state.originalAudio && state.croppedVideo,'JsSIP must send cropped video with the original microphone');
      assert.deepEqual(state.videoSize,[640,360]);
      assert.deepEqual(state.tracks,order,'Fixture did not reproduce capture order');
      assert.deepEqual(state.outgoingOrder,order,'Fixture did not reorder the processed outgoing stream');
      await page.locator('#hangup').click();
      await page.waitForFunction(() => !document.body.classList.contains('busy'));
      assert(await page.evaluate(() => testMedia.getTracks().every(t=>t.readyState === 'ended')));
      assert(await page.evaluate(() => testOutgoing.every(t=>t.readyState === 'ended')));
      console.log('PASS real JsSIP offer, cropped video, original audio and cleanup: track order '+order+' -> SDP audio,video');
    }
    assert.deepEqual(errors,[]);
    await context.close();
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode=1; });
