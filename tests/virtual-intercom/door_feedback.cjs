// Runs the real page in a fresh Chromium. All HTTP and SIP are local fixtures:
// no server session, resident notification or physical command is created.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const assets = path.join(__dirname, '../../static/virtual-intercom');
const output = process.env.VI_BROWSER_OUTPUT || '/tmp/virtual-intercom-door-feedback';
const fakeSip = `
window.testSessions = [];
class Events { constructor(){this.handlers={};} on(name, fn){(this.handlers[name]??=[]).push(fn);} emit(name, arg){for(const fn of this.handlers[name]||[])fn(arg);} }
class Session extends Events {
 constructor(){super();this.ended=false;this.connection={addEventListener(){},getTransceivers(){return[];}};}
 isEnded(){return this.ended;}
 terminate(){this.ended=true;this.emit('ended');}
}
window.JsSIP={debug:{disable(){}},WebSocketInterface:class {},UA:class extends Events {
 start(){queueMicrotask(()=>this.emit('connected'));} stop(){}
 call(target,options){const s=new Session();s.media=options.mediaStream;window.testSessions.push(s);setTimeout(()=>{s.emit('progress');s.emit('confirmed');},0);return s;}
}};`;
(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({headless: true,
    executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    args:['--use-fake-device-for-media-stream','--use-fake-ui-for-media-stream','--autoplay-policy=no-user-gesture-required']});
  try {
    async function fixture(ios = false, holdCancel = false) {
      const context = await browser.newContext({viewport:{width:1200,height:900},permissions:['camera','microphone'],
        ...(ios ? {userAgent:'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 Version/18.5 Mobile/15E148 Safari/604.1'} : {})});
      const page = await context.newPage(), errors = [], frames = [], states = new Map(); let count = 0;
      page.on('pageerror', e => errors.push(e.message));
      await page.addInitScript(() => {
        const acquire = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);
        navigator.mediaDevices.getUserMedia = async options => {
          window.testCapture = await acquire(options); return testCapture;
        };
      });
      await page.route('**/*', async route => {
        const u = new URL(route.request().url());
        if(u.hostname !== 'virtual-intercom.test') throw new Error('Unexpected external request');
        const reply = data => route.fulfill({json: {ok:true,...data}});
        if(u.pathname === '/v/fixturePanel') return route.fulfill({path:path.join(assets,'index.html')});
        if(u.pathname.startsWith('/virtual-intercom/assets/')) {
          const name = u.pathname.slice('/virtual-intercom/assets/'.length);
          assert(['app.js','preview.js','style.css','fonts/SourceSansPro-Regular.ttf','fonts/SourceSansPro-SemiBold.ttf','fonts/SourceSansPro-Bold.ttf'].includes(name));
          return route.fulfill({path:path.join(assets,name)});
        }
        if(u.pathname.endsWith('/jssip.min.js')) return route.fulfill({contentType:'application/javascript',body:fakeSip});
        if(u.pathname.endsWith('/api/panel')) return reply({title:'ЦД2 · Главный вход',subtitle:'Выберите, кому позвонить',listEnabled:true,flats:[{id:1,number:'1'}]});
        if(u.pathname.endsWith('/api/session')) {
          const id = 'fixture-' + ++count; states.set(id,{status:'answered',doorStatus:'idle'});
          return reply({id,token:'fixture-token',sip:{username:'fixture',password:'fixture-password',domain:'virtual-intercom.test',ws:'wss://virtual-intercom.test/wss',iceServers:[]}});
        }
        if(u.pathname.endsWith('/api/status')) {
          const state = states.get(u.searchParams.get('id'));
          if(state.httpError) return route.fulfill({status:503,json:{ok:false,error:'fixture outage'}});
          return reply(state);
        }
        if(u.pathname.endsWith('/api/frame')) { frames.push(route.request().postDataBuffer()); return reply({}); }
        if(u.pathname.endsWith('/api/cancel')) { if (holdCancel) return; return reply({}); }
        if(u.pathname === '/favicon.ico') return route.fulfill({status:204});
        throw new Error('Unexpected request: ' + u.pathname);
      });
      await page.goto('https://virtual-intercom.test/v/fixturePanel');
      await page.locator('#entrance-title').getByText('ЦД2 · Главный вход').waitFor();
      await page.locator('#apartment').fill('1');
      const start = async () => {
        const next = count + 1;
        await page.locator('#call').click();
        await page.waitForFunction(n => window.testSessions?.length === n && document.querySelector('#status').textContent === 'Вы на связи',next);
      };
      const end = async () => {
        await page.evaluate(() => window.testSessions.at(-1).terminate());
        await page.waitForFunction(() => !document.body.classList.contains('busy'));
      };
      return {context,page,states,errors,frames,start,end};
    }
    {
      const t = await fixture(true); await t.start();
      await t.page.locator('#mute').click();
      assert.equal(await t.page.locator('#mute').getAttribute('aria-pressed'),'true');
      assert(await t.page.evaluate(()=>!testSessions[0].media.getAudioTracks()[0].enabled));
      await t.page.locator('#mute').click();
      assert.equal(await t.page.locator('#mute').getAttribute('aria-pressed'),'false');
      assert(await t.page.evaluate(()=>testSessions[0].media.getAudioTracks()[0].enabled));
      await t.page.locator('#preview-canvas').waitFor({state:'visible'});
      assert(await t.page.evaluate(() => {
        const preview = document.querySelector('#preview').srcObject, sip = testSessions[0].media;
        return preview.getAudioTracks().length === 0 && sip.getAudioTracks().length === 1 &&
          preview.getVideoTracks()[0] !== sip.getVideoTracks()[0] &&
          sip.getAudioTracks()[0] === testCapture.getAudioTracks()[0] &&
          sip.getVideoTracks()[0].getSettings().width === 640 && sip.getVideoTracks()[0].getSettings().height === 360;
      }), 'SIP must use the cropped video and original microphone');
      const first = await t.page.locator('#preview-canvas').evaluate(c=>c.toDataURL());
      await t.page.waitForTimeout(500);
      assert.notEqual(await t.page.locator('#preview-canvas').evaluate(c=>c.toDataURL()), first, 'Preview stopped updating after the native layer became transparent');
      assert(t.frames.length>0,'No notification frame uploaded');
      const jpeg = await t.page.evaluate(async base64=>{
        const bitmap = await createImageBitmap(new Blob([Uint8Array.from(atob(base64),c=>c.charCodeAt(0))],{type:'image/jpeg'}));
        const size = [bitmap.width,bitmap.height]; bitmap.close(); return size;
      },t.frames[0].toString('base64'));
      assert.deepEqual(jpeg,[480,270],'Notification preview must also be 16:9');
      await t.end();
      assert.equal(await t.page.locator('#preview-canvas').isHidden(),true);
      assert(await t.page.evaluate(()=>testSessions[0].media.getTracks().every(t=>t.readyState==='ended')));
      assert(await t.page.evaluate(()=>testCapture.getTracks().every(t=>t.readyState==='ended')));
      assert.deepEqual(t.errors,[]); await t.context.close();
      console.log('PASS live cropped SIP video, original audio/mute, 16:9 JPEG and raw camera/output cleanup');
    }
    {
      const t = await fixture(false, true); await t.start();
      await t.page.locator('#hangup').click();
      await t.page.waitForFunction(()=>testCapture.getTracks().every(t=>t.readyState==='ended'),null,{timeout:1000});
      assert(await t.page.evaluate(()=>testSessions[0].ended && testSessions[0].media.getTracks().every(t=>t.readyState==='ended')));
      await t.page.waitForFunction(()=>!document.body.classList.contains('busy'),null,{timeout:7000});
      assert.deepEqual(t.errors,[]); await t.context.close();
      console.log('PASS hung cancel API cannot keep the SIP session or camera/microphone active');
    }
    {
      const t = await fixture();
      await t.page.evaluate(()=>{document.querySelector('#preview-canvas').captureStream=undefined});
      await t.page.locator('#call').click();
      await t.page.waitForFunction(()=>document.querySelector('#status').classList.contains('error'));
      assert.equal(t.states.size,0,'Failed video preparation created a call');
      assert(await t.page.evaluate(()=>testCapture.getTracks().every(t=>t.readyState==='ended')));
      assert.equal(await t.page.locator('#preview').evaluate(v=>v.srcObject),null);
      assert.deepEqual(t.errors,[]); await t.context.close();
      console.log('PASS unavailable capture releases camera and microphone before creating a server session');
    }
    {
      const t = await fixture(); await t.start();
      t.states.get('fixture-1').doorStatus = 'sent';
      await t.page.getByRole('alertdialog').waitFor({state:'visible'});
      for(const viewport of [{width:1200,height:900},{width:390,height:844},{width:320,height:568},{width:844,height:390}]) {
        await t.page.setViewportSize(viewport);
        const dims = await t.page.locator('#door-opened').evaluate(el=>({width:el.clientWidth,scroll:el.scrollWidth}));
        assert(dims.scroll <= dims.width, 'Dialog overflows horizontally');
        const button = await t.page.locator('#door-opened-dismiss').boundingBox();
        assert(button.y>=0 && button.y+button.height<=viewport.height, 'Dismiss button is clipped');
        await t.page.screenshot({path:path.join(output,`opened-${viewport.width}x${viewport.height}.png`)});
      }
      t.states.get('fixture-1').httpError = true;
      await t.end();
      assert(await t.page.locator('#door-opened').isVisible(), 'Hangup or status failure hid the confirmation');
      assert.equal(await t.page.locator('#preview').evaluate(el=>el.srcObject),null);
      await t.page.locator('#door-opened-dismiss').click();
      assert.equal(await t.page.locator('#door-opened').isVisible(),false);
      t.states.get('fixture-1').httpError = false;
      await t.start();
      assert.equal(await t.page.locator('#door-opened').isVisible(),false,'Previous success survived a new call');
      assert.deepEqual(t.errors,[]); await t.context.close();
      console.log('PASS confirmed opening, four viewport sizes, hangup/fetch failure persistence, camera stopped, dismissal and new-call reset');
    }
    {
      const t = await fixture(); await t.start();
      t.states.set('fixture-1',{status:'ended',doorStatus:'sending'});
      await t.end();
      assert.equal(await t.page.locator('#door-opened').isVisible(),false);
      t.states.get('fixture-1').doorStatus = 'sent';
      await t.page.getByRole('alertdialog').waitFor({state:'visible'});
      await t.page.locator('#door-opened-dismiss').click();
      await t.page.waitForTimeout(1200);
      assert.equal(await t.page.locator('#door-opened').isVisible(),false,'Polling reopened dismissed confirmation');
      assert.deepEqual(t.errors,[]); await t.context.close();
      console.log('PASS result arriving after SIP hangup, pending is not success, no repeated dialog');
    }
    {
      const t = await fixture(); await t.start(); await t.end(); await t.start();
      t.states.get('fixture-1').doorStatus = 'sent';
      await t.page.waitForTimeout(1200);
      assert.equal(await t.page.locator('#door-opened').isVisible(),false,'Old call result leaked into a new call');
      assert.deepEqual(t.errors,[]); await t.context.close();
      console.log('PASS stale call results cannot affect the next call');
    }
    {
      const t = await fixture(); await t.start(); t.states.get('fixture-1').doorStatus = 'error';
      await t.page.waitForFunction(expected => document.querySelector('#status').textContent.includes(expected), 'Не удалось подтвердить');
      assert.equal(await t.page.locator('#door-opened').isVisible(),false);
      await t.end(); assert.equal(await t.page.locator('#door-opened').isVisible(),false);
      assert.deepEqual(t.errors,[]); await t.context.close();
      console.log('PASS failed opening does not show a success dialog');
    }
  } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});
