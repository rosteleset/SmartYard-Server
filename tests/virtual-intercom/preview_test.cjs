// Synthetic camera and local WebRTC peers only. No RBT session, push or door I/O.
const {chromium}=require('playwright');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const assets=path.join(__dirname,'../../static/virtual-intercom');
const ios='Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 Version/18.5 Mobile/15E148 Safari/604.1';
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH||'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',args:['--autoplay-policy=no-user-gesture-required']});
 try {
  async function fixture(userAgent=ios,brokenCanvas=false) {
   const context=await browser.newContext({userAgent,viewport:{width:390,height:844}}),page=await context.newPage(),errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',route=>route.fulfill({status:204}));
   await page.setContent('<section style="position:relative;width:320px;height:180px"><video id="preview" autoplay muted playsinline></video><canvas id="preview-canvas" hidden></canvas></section><video id="received" autoplay muted playsinline></video>');
   await page.addStyleTag({content:fs.readFileSync(path.join(assets,'style.css'),'utf8')});
   await page.addScriptTag({content:fs.readFileSync(path.join(assets,'preview.js'),'utf8')});
   await page.evaluate(async broken=>{
    window.source=document.createElement('canvas');source.width=240;source.height=320;
    window.color='#00ff00';window.frames=0;window.pattern=null;
    function paint(){
     const c=source.getContext('2d');c.fillStyle=color;c.fillRect(0,0,source.width,source.height);
     if(pattern){
      c.fillStyle='#ff00ff';c.fillRect(0,0,source.width,source.height);
      const [x,y,w,h]=pattern;
      c.fillStyle='#00ff00';c.fillRect(x,y,w/2,h);c.fillStyle='#0000ff';c.fillRect(x+w/2,y,w/2,h);
      c.fillStyle='#fff';c.fillRect(source.width/2-10,source.height/2-10,20,20);
     }
     c.fillStyle='#000';c.fillRect(10+(frames++%10)*5,source.height/2+30,3,3);
    }
    paint();window.stream=source.captureStream(24);window.drawTimer=setInterval(paint,40);
    const video=document.querySelector('#preview'),canvas=document.querySelector('#preview-canvas');
    video.srcObject=stream;await video.play();
    if(broken)canvas.getContext=()=>null;
    try {window.pipeline=VirtualIntercomPreview.start(video,canvas)} catch(e){window.startError=e.message}
   },brokenCanvas);
   return {page,context,errors};
  }
  const t=await fixture(),p=t.page;
  const snapshot=()=>p.locator('#preview-canvas').evaluate(c=>({w:c.width,h:c.height,hidden:c.hidden,pixel:[...c.getContext('2d').getImageData(20,20,1,1).data]}));
  await p.waitForFunction(()=>!document.querySelector('#preview-canvas').hidden);
  assert.deepEqual((await snapshot()).pixel,[0,255,0,255]);
  assert(await p.evaluate(()=>pipeline.stream.getVideoTracks()[0]!==stream.getVideoTracks()[0]));
  // Swap source dimensions and pixels for less than the stabilizing interval.
  await p.evaluate(()=>{source.width=320;source.height=240;color='#ff0000'});
  await p.waitForFunction(()=>document.querySelector('#preview').videoWidth===320);
  await p.waitForTimeout(100);
  assert.deepEqual(await snapshot(),{w:640,h:360,hidden:false,pixel:[0,255,0,255]},'Transient rotated frame reached the preview');
  await p.evaluate(()=>{source.width=240;source.height=320;color='#0000ff'});
  await p.waitForFunction(()=>document.querySelector('#preview-canvas').getContext('2d').getImageData(20,20,1,1).data[2]===255);
  console.log('PASS transient dimension/rotation flip retains the last good frame and resumes live pixels');
  await p.evaluate(()=>{source.width=320;source.height=240;color='#ff0000'});
  await p.waitForFunction(()=>document.querySelector('#preview-canvas').getContext('2d').getImageData(20,20,1,1).data[0]===255);
  assert.deepEqual((await snapshot()).pixel,[255,0,0,255]);
  assert.equal(await p.locator('#preview').evaluate(v=>getComputedStyle(v).opacity),'0');
  assert.equal(await p.locator('#preview-canvas').evaluate(c=>getComputedStyle(c).transform),'matrix(-1, 0, 0, 1, 0, 0)');

  // Known crop boundaries: portrait, 4:3, ultrawide, and already 16:9.
  for(const [w,h,bounds,square] of [[240,320,[0,92.5,240,135],53],[640,480,[0,60,640,360],20],[960,360,[160,0,640,360],20],[640,360,[0,0,640,360],20]]) {
   await p.evaluate(({w,h,bounds})=>{source.width=w;source.height=h;pattern=bounds},{w,h,bounds});
   await p.waitForFunction(w=>document.querySelector('#preview').videoWidth===w,w);
   await p.waitForTimeout(450);
   const pixels=await p.locator('#preview-canvas').evaluate(c=>{
    const ctx=c.getContext('2d'),pixel=(x,y)=>[...ctx.getImageData(x,y,1,1).data];
    // Count the midpoint of antialiased edges after scaling the source square.
    const white=(x,y)=>pixel(x,y).slice(0,3).every(v=>v>127);
    return {w:c.width,h:c.height,left:pixel(10,10),right:pixel(630,350),
     squareWidth:Array.from({length:640},(_,x)=>white(x,180)).filter(Boolean).length,
     squareHeight:Array.from({length:360},(_,y)=>white(320,y)).filter(Boolean).length};
   });
   assert.deepEqual([pixels.w,pixels.h],[640,360]);
   assert.deepEqual(pixels.left,[0,255,0,255]);assert.deepEqual(pixels.right,[0,0,255,255]);
   assert(Math.abs(pixels.squareWidth-square)<=2 && Math.abs(pixels.squareHeight-square)<=2,`Centered square was stretched or crop scale is wrong for ${w}x${h}: ${JSON.stringify(pixels)}`);
  }
  console.log('PASS portrait, 4:3, ultrawide and landscape are center-cropped to 640x360 without stretching or mirroring outgoing pixels');

  // Encode and decode the portrait crop through actual H.264 WebRTC, not just CSS.
  await p.evaluate(async()=>{
   source.width=240;source.height=320;pattern=[0,92.5,240,135];
   window.sender=new RTCPeerConnection({iceServers:[]});window.receiver=new RTCPeerConnection({iceServers:[]});
   const transceiver=sender.addTransceiver(pipeline.stream.getVideoTracks()[0],{direction:'sendonly',streams:[pipeline.stream]});
   const h264=RTCRtpSender.getCapabilities('video').codecs.filter(c=>c.mimeType==='video/H264');
   if(!h264.length)throw Error('H264 is unavailable in this test browser');
   transceiver.setCodecPreferences(h264);
   receiver.ontrack=e=>{document.querySelector('#received').srcObject=e.streams[0]};
   async function gather(pc,description){
    await pc.setLocalDescription(description);
    if(pc.iceGatheringState!=='complete')await new Promise(resolve=>pc.addEventListener('icegatheringstatechange',()=>{if(pc.iceGatheringState==='complete')resolve()}));
   }
   await gather(sender,await sender.createOffer());await receiver.setRemoteDescription(sender.localDescription);
   await gather(receiver,await receiver.createAnswer());await sender.setRemoteDescription(receiver.localDescription);
  });
  await p.waitForFunction(()=>{const v=document.querySelector('#received');return v.videoWidth===640 && v.videoHeight===360 && v.readyState>=2});
  const decoded=()=>p.evaluate(async()=>[...(await receiver.getStats()).values()].find(s=>s.type==='inbound-rtp'&&s.kind==='video').framesDecoded);
  const before=await decoded();await p.waitForTimeout(600);assert((await decoded())>before+2,'H264 frames stopped progressing');
  const received=await p.locator('#received').evaluate(v=>{
   const c=document.createElement('canvas');c.width=v.videoWidth;c.height=v.videoHeight;const ctx=c.getContext('2d');ctx.drawImage(v,0,0);
   return {left:[...ctx.getImageData(10,10,1,1).data],right:[...ctx.getImageData(630,350,1,1).data]};
  });
  // H.264's RGB/YUV conversion changes channel levels. Check the dominant
  // colors and alpha, while the lossless checks above verify exact crop edges.
  const dominant=(pixel,channel)=>pixel[3]===255 && pixel[channel]>150+Math.max(...pixel.slice(0,3).filter((_,i)=>i!==channel));
  assert(dominant(received.left,1) && dominant(received.right,2),`Received H264 has wrong crop or mirroring: ${JSON.stringify(received)}`);
  console.log('PASS real H264 WebRTC receiver decodes advancing 640x360 frames with the same crop');

  await p.evaluate(()=>{sender.close();receiver.close();window.outgoing=pipeline.stream.getVideoTracks()[0];pipeline.stop();pipeline.stop()});
  assert.deepEqual(await p.locator('#preview-canvas').evaluate(c=>({w:c.width,h:c.height,hidden:c.hidden})),{w:1,h:1,hidden:true});
  assert.equal(await p.evaluate(()=>outgoing.readyState),'ended');
  assert.equal(await p.evaluate(()=>stream.getVideoTracks()[0].readyState),'live','Renderer stopped its externally owned camera source');
  await p.waitForTimeout(100);assert.equal((await snapshot()).hidden,true,'Stopped renderer painted a late frame');
  await p.evaluate(()=>{window.pipeline=VirtualIntercomPreview.start(document.querySelector('#preview'),document.querySelector('#preview-canvas'))});
  await p.waitForFunction(()=>!document.querySelector('#preview-canvas').hidden);
  assert.equal(await p.evaluate(()=>pipeline.stream.getVideoTracks()[0].readyState),'live');
  assert.deepEqual(t.errors,[]);await t.context.close();
  console.log('PASS sustained rotation, preview mirroring, output-track disposal and clean restart');
  for(const [agent,broken] of [['Mozilla/5.0 Chrome/120',false],[ios,true]]) {
   const t=await fixture(agent,broken);await t.page.waitForTimeout(150);
   assert.equal(await t.page.locator('#preview-canvas').isHidden(),broken);
   assert.equal(await t.page.evaluate(()=>!!window.startError),broken);
   assert.deepEqual(t.errors,[]);await t.context.close();
  }
  console.log('PASS desktop uses the same crop; unavailable canvas reports an explicit setup error');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exitCode=1});
