// Real visitor UI with local HTTP/SIP fixtures. No resident is called.
const {chromium} = require('playwright');
const assert = require('node:assert/strict'), path = require('node:path'), fs = require('node:fs');
const assets = path.resolve(__dirname, '../../static/virtual-intercom');
const output = process.env.VI_BROWSER_OUTPUT || '/tmp/virtual-intercom-flat-names';
const title = 'Офис Рога и Копыта';
const flats = [{id:120,number:'12',name:title},{id:130,number:'13',name:''},
  {id:140,number:'14',name:'<img src=x onerror="window.injected=true">'},
  {id:150,number:'15',name:'ДлинноеНазвание'.repeat(8)}, {id:160,number:'16'}];
const sip = `
class Events {constructor(){this.handlers={}} on(n,f){(this.handlers[n]??=[]).push(f)} emit(n){for(const f of this.handlers[n]||[])f()}}
class Session extends Events {constructor(){super();this.ended=false;this.connection={addEventListener(){},getTransceivers(){return []}}} isEnded(){return this.ended} terminate(){this.ended=true;this.emit('ended')}}
window.JsSIP={debug:{disable(){}},WebSocketInterface:class{},UA:class extends Events{start(){queueMicrotask(()=>this.emit('connected'))}stop(){}call(){const s=new Session();setTimeout(()=>s.emit('progress'),0);return s}}};`;
(async()=>{
fs.mkdirSync(output,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH||'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',args:['--use-fake-device-for-media-stream','--use-fake-ui-for-media-stream']});
try {
 const context=await browser.newContext({permissions:['camera','microphone'],viewport:{width:1200,height:1000}});
 const page=await context.newPage(), calls=[], errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 await page.route('**/*',async route=>{
  const u=new URL(route.request().url()); assert.equal(u.hostname,'virtual-intercom.test');
  const reply=data=>route.fulfill({json:{ok:true,...data}});
  if(u.pathname==='/v/fixturePanel')return route.fulfill({path:path.join(assets,'index.html')});
  if(u.pathname.startsWith('/virtual-intercom/assets/')){const name=u.pathname.slice('/virtual-intercom/assets/'.length);assert(['app.js','preview.js','style.css','fonts/SourceSansPro-Regular.ttf','fonts/SourceSansPro-SemiBold.ttf','fonts/SourceSansPro-Bold.ttf'].includes(name));return route.fulfill({path:path.join(assets,name)})}
  if(u.pathname.endsWith('/jssip.min.js'))return route.fulfill({contentType:'application/javascript',body:sip});
  if(u.pathname.endsWith('/api/panel'))return reply({title:'Главный вход',subtitle:'Выберите квартиру',listEnabled:true,flats});
  if(u.pathname.endsWith('/api/session')){calls.push(route.request().postDataJSON());return reply({id:'fixture',token:'fixture',sip:{username:'fixture',password:'fixture',domain:u.hostname,ws:'wss://virtual-intercom.test/wss',iceServers:[]}})}
  if(u.pathname.endsWith('/api/status'))return reply({status:'ringing',doorStatus:'idle'});
  if(/\/api\/(cancel|frame)$/.test(u.pathname))return reply({});
  if(u.pathname==='/favicon.ico')return route.fulfill({status:204});
  throw Error('Unexpected request '+u.pathname);
 });
 await page.goto('https://virtual-intercom.test/v/fixturePanel');
 await page.evaluate(()=>document.fonts.ready);
 await page.screenshot({path:path.join(output,'keypad-desktop.png'),fullPage:true});
 await page.locator('#list-tab').click();
 await page.getByRole('button',{name:title,exact:true}).waitFor();
 assert.equal(await page.locator('.apartment-name').allTextContents().then(x=>JSON.stringify(x)), JSON.stringify(flats.map(f=>f.name||'Квартира '+f.number)));
 assert.equal(await page.locator('#apartments img').count(),0);
 assert.equal(await page.evaluate(()=>window.injected),undefined);
 for(const query of ['РОГА','копыта','12']){
  await page.locator('#search').fill(query);
  assert.deepEqual(await page.locator('.apartment-name').allTextContents(),[title]);
 }
 await page.getByRole('button',{name:title,exact:true}).click();
 assert.equal(await page.locator('#apartment').inputValue(),'12');
 assert.equal(await page.locator('#status').textContent(),title+' · Готово к вызову');
 await page.locator('#call').click();
 await page.waitForFunction(t=>document.querySelector('#status').textContent==='Звоним: '+t+'…',title);
 assert.deepEqual(calls[0],{panel:'fixturePanel',flatId:120});
 await page.locator('#hangup').click();
 await page.waitForFunction(()=>!document.body.classList.contains('busy'));
 await page.locator('#keypad-tab').click();
 await page.locator('#apartment').fill('13');
 assert.equal(await page.locator('#status').textContent(),'Квартира 13 · Готово к вызову');
 await page.locator('#apartment').fill('12');
 assert.equal(await page.locator('#status').textContent(),title+' · Готово к вызову');
 await page.locator('#call').click();
 await page.waitForFunction(t=>document.querySelector('#status').textContent==='Звоним: '+t+'…',title);
 assert.deepEqual(calls[1],calls[0], 'Numeric dialing changed the call target');
 await page.locator('#hangup').click();
 await page.waitForFunction(()=>!document.body.classList.contains('busy'));
 await page.locator('#list-tab').click(); await page.locator('#search').fill('');
 for(const width of [1200,390,320]){
  await page.setViewportSize({width,height:1000});
  const overflow=await page.evaluate(()=>{
   const list=document.querySelector('#apartments');
   return document.documentElement.scrollWidth>innerWidth || list.scrollWidth>list.clientWidth+1 || [...list.querySelectorAll('button')].some(b=>b.scrollWidth>b.clientWidth+1);
  });
  assert.equal(overflow,false,'Name overflows at '+width);
  await page.screenshot({path:path.join(output,'names-'+width+'.png'),fullPage:true});
 }
 assert.deepEqual(errors,[]);
 console.log('PASS custom/default labels, case-insensitive name/number search, escaped HTML, same call target from list/keypad and long names at 1200/390/320px');
}finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
