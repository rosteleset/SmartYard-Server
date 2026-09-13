// Exercises the versioned module and native cardForm/modal in a fresh browser.
// API responses are fixtures; no authentication, call or physical command occurs.
const {chromium}=require('playwright');
const fs=require('node:fs'), path=require('node:path'), assert=require('node:assert/strict');
const repo=path.resolve(__dirname, '../..');
const output=process.env.VI_BROWSER_OUTPUT || '/tmp/virtual-intercom-admin-form';
const libraries={'adminlte.min.css':'AdminLTE/dist/css/adminlte.min.css','jquery.min.js':'AdminLTE/plugins/jquery/jquery.min.js','jquery-ui.min.js':'AdminLTE/plugins/jquery-ui/jquery-ui.min.js','bootstrap.bundle.min.js':'AdminLTE/plugins/bootstrap/js/bootstrap.bundle.min.js','qrcode.min.js':'qrcodejs/qrcode.min.js'};
const read=p=>fs.readFileSync(path.join(repo,p),'utf8');
const fn=(file,name)=>read(file).match(new RegExp('^function '+name+'\\([\\s\\S]*?^}', 'm'))[0];
const translations=JSON.parse(read('client/modules/addresses/i18n/ru.json'));
const helpers=['xblur','autoZ','escapeHTML','parseIntEx'].map(n=>fn('client/js/utils.js',n)).join('\n')+'\n'+fn('client/js/widgets.js','modal');
const bootstrap=`
window.version='fixture';window.config={defaultLanguage:'ru'};window.lStore=()=>null;window.lang={addresses:{}};
window.availableFonts=[]; window.queryLocalFonts=async()=>[];
$.browser={mozilla:false};
window.i18n=k=>(${JSON.stringify(translations)})[k.replace(/^addresses\\./,'')]||({yes:'Да',no:'Нет',cancel:'Отмена'})[k]||k;
window.moduleLoaded=(name,module)=>{window.module=module};
window.canEdit=true; window.AVAIL=()=>window.canEdit;
window.loadingStart=()=>{};window.loadingDone=()=>{};window.message=()=>{};
window.FAIL=()=>{window.failures++};window.failures=0;window.error=(...args)=>{throw Error(args.join(' '))};
window.saved={entranceId:112,available:true,enabled:false,title:'Вход 1',subtitle:'Выберите квартиру',listEnabled:true,url:null};
window.writes=[];window.rejectSave=false;
window.GET=()=>$.Deferred().resolve({virtualIntercom:structuredClone(window.saved)}).promise();
window.PUT=(resource,action,id,values)=>{window.writes.push(values);const d=$.Deferred();setTimeout(()=>{if(window.rejectSave){d.reject({});return}Object.assign(window.saved,values,{url:window.saved.url||'https://virtual-intercom.test/v/fixturePanel'}); d.resolve({virtualIntercom:structuredClone(window.saved)})},20);return d.promise()};
`;
(async()=>{
fs.mkdirSync(output,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'});
try{
const page=await browser.newPage({viewport:{width:1200,height:1000}}),errors=[];page.on('pageerror',e=>errors.push(e.message));
await page.route('**/*',async route=>{
 const u=new URL(route.request().url());
 if(u.hostname!=='virtual-intercom.test') throw Error('Unexpected external request');
 if(u.pathname==='/') return route.fulfill({contentType:'text/html',body:'<!doctype html><html lang="ru"><meta charset="utf-8"><link rel="stylesheet" href="/adminlte.min.css"><body><div class="modal p-0" id="modal" role="dialog"><div class="modal-dialog p-0" role="document" style="margin-top:25px"><div class="modal-content p-0" id="modalBody"></div></div></div></body></html>'});
 if(u.pathname.endsWith('/virtualIntercom/style.css'))return route.fulfill({path:path.join(repo,'client/modules/addresses/virtualIntercom/style.css')});
 const name=path.basename(u.pathname);if(['adminlte.min.css','jquery.min.js','jquery-ui.min.js','bootstrap.bundle.min.js','qrcode.min.js'].includes(name))return route.fulfill({path:process.env.VI_ADMIN_LIBS ? path.join(process.env.VI_ADMIN_LIBS,name) : path.join(repo,'client/lib',libraries[name])});
 return route.fulfill({status:204});
});
await page.goto('https://virtual-intercom.test/');
// Native RBT styles include nowrap and width rules for cardForm cells.
await page.addStyleTag({content:read('client/css/index.css')});
for(const name of ['jquery.min.js','jquery-ui.min.js','bootstrap.bundle.min.js','qrcode.min.js'])await page.addScriptTag({url:'/'+name});
await page.addScriptTag({content:read('client/js/phpjs.js')});
await page.addScriptTag({content:read('client/js/clipboard.min.js')});
await page.addScriptTag({content:helpers+'\n'+bootstrap});
await page.addScriptTag({content:read('client/js/form.js')});
await page.addScriptTag({content:read('client/modules/addresses/virtualIntercom.js')});
await page.evaluate(()=>window.module.edit(112));
const field=id=>page.locator('[id$="-'+id+'"]');
assert.equal(await field('allowAllFlats').inputValue(),'1');
await field('allowAllFlats').selectOption('0');
await field('title').fill('Главный вход <проверка>');await field('enabled').selectOption('1');
await page.locator('.formOk').last().click();
await page.getByRole('link',{name:'Открыть панель'}).waitFor();
assert.equal(await field('title').inputValue(),'Главный вход <проверка>');
assert.equal(await field('url').inputValue(),'https://virtual-intercom.test/v/fixturePanel');
assert.equal(await page.locator('[id$="-qr"] canvas').count(),1);
assert.deepEqual(await page.evaluate(()=>window.writes[0]),{enabled:true,title:'Главный вход <проверка>',subtitle:'Выберите квартиру',listEnabled:true,allowAllFlats:false});
assert.equal(await field('allowAllFlats').inputValue(),'0');
const downloadEvent=page.waitForEvent('download');await page.getByRole('button',{name:'Скачать QR-код'}).click();const download=await downloadEvent;assert.equal(download.suggestedFilename(),'virtual-intercom-112.png');
await page.screenshot({path:path.join(output,'saved.png')});
console.log('PASS real cardForm save/reopen, booleans, escaped title, generated link, QR and PNG download');
for(const viewport of [{width:1200,height:1000},{width:820,height:900},{width:600,height:900},{width:390,height:844},{width:320,height:568}]) {
 await page.setViewportSize(viewport);
 const clipped=await page.locator('#modalBody').evaluate(modal=>{
  const body=modal.querySelector('.card-body'), bounds=body.getBoundingClientRect();
  const result=[];
  if(body.scrollWidth>body.clientWidth+1)result.push('form scroll width: '+body.scrollWidth+' > '+body.clientWidth);
  for(const el of modal.querySelectorAll('td,input,select,button,a,canvas,img')) {
   const r=el.getBoundingClientRect();
   if(r.width && (r.left<bounds.left-1 || r.right>bounds.right+1))result.push(el.tagName+': '+(el.id||el.textContent.trim()));
  }
  return result;
 });
 assert.deepEqual(clipped,[], 'Form overflows at '+viewport.width+'px');
 await page.screenshot({path:path.join(output,'width-'+viewport.width+'.png')});
}
await page.setViewportSize({width:1200,height:1000});
console.log('PASS native RBT CSS, wrapped description and controls/QR inside modal at five widths');
await page.evaluate(()=>window.rejectSave=true);await field('title').fill('Несохранённое название');await page.locator('.formOk').last().click();
await page.waitForFunction(()=>window.failures===1 && document.querySelector('[id$="-title"]')?.value==='Несохранённое название' && document.querySelector('#modal').classList.contains('show'));
assert.equal(await page.evaluate(()=>window.saved.title),'Главный вход <проверка>');
console.log('PASS save failure retains draft and does not claim persisted changes');
await page.evaluate(()=>{window.canEdit=false;window.module.edit(112)});
assert.equal(await page.locator('.formOk:visible').count(),0);assert(await field('title').isDisabled());assert(await field('enabled').isDisabled());assert(await field('allowAllFlats').isDisabled());assert(await field('url').isEnabled());assert.equal(await field('url').evaluate(el=>el.readOnly),true);
await page.getByRole('link',{name:'Открыть панель'}).waitFor();
assert.deepEqual(errors,[]);
console.log('PASS read-only permissions retain selectable link and QR; no browser exceptions');

// The new definition uses the existing apartment editor and serializer.
await page.evaluate(()=>{window.modules={addresses:{},custom:{}};window.moduleLoaded=(name,value)=>{window.modules.addresses.houses=value}});
await page.addScriptTag({content:read('client/modules/addresses/houses.js')});
await page.evaluate(()=>{
 const houses=modules.addresses.houses;
 houses.customFieldsConfiguration={flat:[{applyTo:'flat',catalog:'virtualIntercom',field:'virtualIntercomName',type:'text',editor:'text',add:1,modify:1,
  fieldDisplay:'addresses.virtualIntercomFlatName',fieldDescription:'addresses.virtualIntercomFlatNameHint',tab:'addresses.virtualIntercom',regex:'^.{0,120}$'},
  {applyTo:'flat',catalog:'virtualIntercom',field:'virtualIntercomCallsEnabled',type:'text',editor:'noyes',add:1,modify:1,
   fieldDisplay:'addresses.virtualIntercomFlatCalls',fieldDescription:'addresses.virtualIntercomFlatCallsHint',tab:'addresses.virtualIntercom'}]};
 window.showNameForm=()=>{const fields=[];houses.appendFlatCustomFields(fields,window.flatValues||{},'modify');
  cardForm({title:'Квартира 12',footer:true,topApply:true,apply:'Сохранить',fields,callback:values=>{window.flatValues=houses.extractFlatCustomFields(values,'modify')}})};
 window.showNameForm();
});
await page.getByText('Название в виртуальном домофоне',{exact:true}).waitFor();
const nameField=field('_cf_virtualIntercomName');
const callsField=field('_cf_virtualIntercomCallsEnabled');
await callsField.selectOption('1');
await nameField.fill('Офис Рога и Копыта');await page.locator('.formOk').last().click();
await page.waitForFunction(()=>window.flatValues?.virtualIntercomName==='Офис Рога и Копыта');
assert.equal(await page.evaluate(()=>window.flatValues.virtualIntercomCallsEnabled),'1');
await page.evaluate(()=>window.showNameForm());assert.equal(await nameField.inputValue(),'Офис Рога и Копыта');
assert.equal(await callsField.inputValue(),'1');
await callsField.selectOption('0');
await nameField.fill('');await page.locator('.formOk').last().click();
await page.waitForFunction(()=>window.flatValues?.virtualIntercomName==='');
assert.deepEqual(errors,[]);
assert.equal(await page.evaluate(()=>window.flatValues.virtualIntercomCallsEnabled),'0');
console.log('PASS native apartment tab, localized name and opt-in fields, edit/reopen, clearing and opt-out');


}finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
