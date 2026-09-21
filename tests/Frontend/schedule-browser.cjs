// Run with PLAYWRIGHT_MODULE pointing to an installed Playwright module if not local.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

(async () => {
    const root = path.resolve(__dirname, '../..');
    const render = scenario => execFileSync('php', [path.join(__dirname, 'render-dashboard.php'), scenario], { cwd: root, encoding: 'utf8' });
    let html = render('default');
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        // Use the exact CDN assets cached in testing/ when browser network access is restricted.
        await page.route('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/**', async route => {
            const name = path.basename(new URL(route.request().url()).pathname);
            const cached = path.join(root, 'storage/framework/testing/ticker', name);
            if (!fs.existsSync(cached)) return route.continue();
            await route.fulfill({ contentType: name.endsWith('.css') ? 'text/css' : 'application/javascript', body: fs.readFileSync(cached) });
        });
        await page.route('http://dashboard.test/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname === '/dashboard') return route.fulfill({ contentType: 'text/html', body: html });
            const filename = path.resolve(root, 'public', '.' + decodeURIComponent(url.pathname));
            if (!filename.startsWith(path.join(root, 'public') + path.sep) || !fs.existsSync(filename)) return route.fulfill({ status: 404, body: '' });
            const contentType = filename.endsWith('.js') ? 'application/javascript' : (filename.endsWith('.css') ? 'text/css' : 'application/octet-stream');
            await route.fulfill({ contentType, body: fs.readFileSync(filename) });
        });
        const output = path.join(root, 'storage/framework/testing/ticker');
        fs.mkdirSync(output, { recursive: true });
        for (const width of [360, 390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto('http://dashboard.test/dashboard', { waitUntil: 'networkidle' });
            await page.locator('.schedule-ticker__clock time').waitFor();
            await page.screenshot({ path: path.join(output, `dashboard-${width}.png`), fullPage: true });
            assert.equal(await page.locator('#employeeDashboard > .card').count(), 2); // schedule + history
            assert.equal(await page.locator('.employee-action-cards .employee-card').count(), 3);
            const overflow = await page.evaluate(() => Array.from(document.querySelectorAll('body *'))
                .filter(node => node.getBoundingClientRect().right > innerWidth + 1 && getComputedStyle(node).position !== 'fixed')
                .slice(0, 8).map(node => ({ tag: node.tagName, class: node.className, right: node.getBoundingClientRect().right })));
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, `Overflow at ${width}px: ${JSON.stringify(overflow)}`);
            const boxes = await page.locator('.employee-action-cards > div').evaluateAll(nodes => nodes.map(node => ({ x: node.offsetLeft, y: node.offsetTop })));
            assert.equal(new Set(boxes.map(box => box.y)).size, width >= 1200 ? 1 : (width >= 768 ? 2 : 3));
            const ticks = page.locator(width < 768 ? '.schedule-ticker__ticks--compact .schedule-ticker__tick' : '.schedule-ticker__ticks--wide .schedule-ticker__tick');
            assert.equal(await ticks.count(), width < 768 ? 3 : 7);
            await page.screenshot({ path: path.join(output, `dashboard-${width}.png`), fullPage: true });
            console.log(`PASS responsive ${width}px`);
        }
        const before = await page.locator('.schedule-ticker__clock time').textContent();
        await page.waitForTimeout(1200);
        assert.notEqual(await page.locator('.schedule-ticker__clock time').textContent(), before);
        assert.ok(Number(await page.getByRole('progressbar').getAttribute('aria-valuenow')) > 0);
        await page.emulateMedia({ reducedMotion: 'reduce' });
        assert.equal(await page.locator('.schedule-ticker__fill').evaluate(node => getComputedStyle(node).transitionDuration), '0s');
        assert.equal(await page.locator('.employee-card--leave').getAttribute('href'), 'http://dashboard.test/my-leave-requests');
        for (const state of ['day_off', 'sick', 'leave', 'unavailable']) {
            html = render(state);
            await page.goto('http://dashboard.test/dashboard', { waitUntil: 'networkidle' });
            await page.locator('.schedule-ticker__empty').waitFor();
            assert.equal(await page.getByRole('progressbar').count(), 0);
        }
        html = render('open');
        await page.goto('http://dashboard.test/dashboard', { waitUntil: 'networkidle' });
        await page.locator('#checkOutCard').click();
        await page.locator('#checkoutModal.show').waitFor();
        assert.equal(await page.locator('#earlyCheckoutReason').isVisible(), true);
        assert.equal(await page.locator('#checkoutModal .modal-content').evaluate(node => getComputedStyle(node).borderTopColor), 'rgb(16, 185, 129)');
        assert.equal(await page.locator('#checkoutModal .checkout-modal__icon').count(), 1);
        await page.locator('#confirmCheckout').click();
        assert.equal(await page.locator('#earlyCheckoutReason').evaluate(node => node.validity.valueMissing), true);
        await page.getByRole('button', { name: 'Batal', exact: true }).click();
        await page.locator('#checkoutModal').waitFor({ state: 'hidden' });
        html = render('admin');
        await page.goto('http://dashboard.test/dashboard', { waitUntil: 'networkidle' });
        await page.waitForFunction(() => window.Chart && window.Chart.getChart('attendanceChart'));
        assert.equal(await page.locator('script[src*="employee-schedule.js"]').count(), 0);
        for (const type of ['doughnut', 'line', 'bar']) {
            await page.locator(`[data-chart-type="${type}"]`).click();
            assert.equal(await page.evaluate(() => window.Chart.getChart('attendanceChart').config.type), type);
        }
        for (const width of [390, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.goto('http://dashboard.test/dashboard', { waitUntil: 'networkidle' });
            assert.equal(await page.locator('.metric-card').count(), 4);
            assert.equal(await page.locator('.metric-card .metric-icon').count(), 0);
            assert.equal(await page.locator('.metric-card progress.metric-progress').count(), 2);
            assert.equal(await page.locator('.sidebar .brand').evaluate(node => getComputedStyle(node).borderBottomStyle), 'solid');
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
            await page.screenshot({ path: path.join(output, `admin-dashboard-${width}.png`), fullPage: true });
            console.log(`PASS admin dashboard responsive ${width}px`);
        }
        console.log('PASS isolated admin Chart.js bundle and all three chart modes');
        assert.deepEqual(errors, []);
        console.log('PASS ticking clock, progress, reduced motion, leave link, four empty states, early checkout modal, and no browser errors');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
