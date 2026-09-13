// Real browser and visitor assets, mocked HTTP. Never opens a physical door.
const { chromium } = require('playwright');
const assert = require('node:assert/strict'), path = require('node:path'), fs = require('node:fs');
const assets = path.resolve(__dirname, '../../static/virtual-intercom');
const output = process.env.VI_BROWSER_OUTPUT || '/tmp/virtual-intercom-codes';
(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
  try {
    for (const listEnabled of [true, false]) {
      const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
      const requests = [], errors = [];
      let outcome = 'success', release;
      page.on('pageerror', error => errors.push(error.message));
      await page.addInitScript(() => {
        navigator.mediaDevices.getUserMedia = () => { throw new Error('Opening by code requested camera or microphone'); };
      });
      await page.route('**/*', async route => {
        const u = new URL(route.request().url());
        assert.equal(u.hostname, 'virtual-intercom.test');
        if (u.pathname === '/v/fixturePanel') return route.fulfill({ path: path.join(assets, 'index.html') });
        if (u.pathname.startsWith('/virtual-intercom/assets/')) {
          const name = u.pathname.slice('/virtual-intercom/assets/'.length);
          assert(!name.includes('..'));
          return route.fulfill({ path: path.join(assets, name) });
        }
        if (u.pathname.endsWith('/jssip.min.js')) return route.fulfill({ contentType: 'application/javascript', body: '' });
        if (u.pathname.endsWith('/api/panel')) return route.fulfill({ json: { ok: true, title: 'Главный вход', subtitle: 'Выберите, кому позвонить', listEnabled, flats: [{ id: 10, number: '12', name: 'Офис' }] } });
        if (u.pathname.endsWith('/api/open-code')) {
          assert.equal(route.request().method(), 'POST');
          requests.push(route.request().postDataJSON());
          if (outcome === 'hold') await new Promise(resolve => { release = resolve; });
          if (outcome === 'error') return route.fulfill({ status: 403, json: { ok: false, error: 'Код неверен или недоступен' } });
          return route.fulfill({ json: { ok: true, doorStatus: 'sent' } });
        }
        if (u.pathname === '/favicon.ico') return route.fulfill({ status: 204 });
        throw new Error('Unexpected request (call or code leak): ' + u.pathname);
      });
      await page.goto('https://virtual-intercom.test/v/fixturePanel');
      await page.locator('#code-mode:not(:disabled)').waitFor();
      await page.locator('#apartment').fill('12');
      assert(await page.locator('#call').isEnabled());
      assert.equal(await page.locator('#call-label').textContent(), 'Позвонить');
      await page.locator('#code-mode').click();
      assert.equal(await page.locator('#apartment').inputValue(), '');
      assert.equal(await page.locator('#apartment').getAttribute('type'), 'password');
      assert.equal(await page.locator('#control-title').textContent(), 'Код открытия двери');
      assert.equal(await page.locator('#call-label').textContent(), 'Открыть дверь');
      assert(await page.locator('#call-icon').isHidden());
      assert(await page.locator('#open-icon').isVisible());
      assert(await page.locator('#call').isDisabled());
      assert.equal(await page.locator('#selection-mode').isVisible(), listEnabled);
      for (const digit of '12345') await page.locator(`[data-digit="${digit}"]`).click();
      await page.locator('[data-digit="6"]').click();
      assert.equal(await page.locator('#apartment').inputValue(), '12345');
      assert(await page.locator('#call').isEnabled());
      for (const width of [320, 390, 1200]) {
        await page.setViewportSize({ width, height: 900 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        await page.screenshot({ path: path.join(output, `code-${listEnabled}-${width}.png`), fullPage: true });
      }
      outcome = 'hold';
      await page.locator('#call').click();
      await page.waitForFunction(() => document.querySelector('#status').textContent === 'Отправляем команду открытия…');
      await page.locator('#apartment').press('Enter');
      await page.locator('[data-digit="1"]').click();
      assert.equal(requests.length, 1);
      assert.equal(await page.locator('#apartment').inputValue(), '');
      assert(await page.locator('#code-mode').isDisabled());
      assert(await page.locator('.camera-panel').isHidden());
      outcome = 'success'; release();
      await page.locator('#door-opened').waitFor({ state: 'visible' });
      assert.deepEqual(requests, [{ panel: 'fixturePanel', code: '12345' }]);
      await page.locator('#door-opened-dismiss').click();
      assert(await page.locator('#call').isDisabled());
      outcome = 'error';
      await page.locator('#apartment').fill('99999');
      await page.locator('#apartment').press('Enter');
      await page.waitForFunction(() => document.querySelector('#status').classList.contains('error'));
      assert(await page.locator('#door-opened').isHidden());
      assert.equal(await page.locator('#apartment').inputValue(), '');
      await page.locator('#code-mode').click();
      assert.equal(await page.locator('#apartment').getAttribute('type'), 'text');
      assert.equal(await page.locator('#call-label').textContent(), 'Позвонить');
      assert(await page.locator('#call-icon').isVisible());
      assert(await page.locator('#open-icon').isHidden());
      await page.locator('#apartment').fill('12');
      assert(await page.locator('#call').isEnabled());
      if (listEnabled) {
        await page.locator('#code-mode').click();
        await page.locator('#apartment').fill('12345');
        await page.locator('#list-tab').click();
        await page.locator('#apartments button').click();
        assert.equal(await page.locator('#call-label').textContent(), 'Позвонить');
        assert.equal(await page.locator('#apartment').inputValue(), '12');
      }
      assert.deepEqual(errors, []);
      await page.close();
      console.log(`PASS list=${listEnabled}: shared keypad, masked code, POST, no media/call, duplicate guard, success/error, clear secret, return to calling, 320/390/1200px`);
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
