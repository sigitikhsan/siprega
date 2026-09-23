const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../../resources/js/employee-attendance.js'), 'utf8');

function fixture(options = {}) {
    const elements = new Map();
    let elapsed = 0;
    let gpsCalls = 0;
    let fetchCalls = 0;
    let modalShows = 0;
    const submitted = [];
    const intervals = [];
    const element = id => {
        if (!elements.has(id)) {
            const events = {};
            const fields = {};
            const classes = new Set();
            elements.set(id, {
                id, disabled: false, hidden: true, required: false, value: '', textContent: '', dataset: {}, focused: false,
                classList: {
                    contains: name => name === 'show' ? modalShows > 0 : classes.has(name),
                    toggle(name, force) {
                        const active = force === undefined ? !classes.has(name) : !!force;
                        if (active) classes.add(name); else classes.delete(name);
                        return active;
                    }
                },
                addEventListener: (name, handler) => { events[name] = handler; },
                fire: (name, event = { preventDefault() {} }) => events[name](event),
                querySelector: () => element(id + '-hint'),
                querySelectorAll: () => [],
                setAttribute() {}, removeAttribute() {}, focus() { this.focused = true; },
                setCustomValidity(message) { this.validationMessage = message; },
                elements: { namedItem: name => fields[name] || (fields[name] = { value: '' }) },
                reportValidity: () => !element('earlyCheckoutReason').required ||
                    (!!element('earlyCheckoutReason').value.trim() && !element('earlyCheckoutReason').validationMessage)
            });
        }
        return elements.get(id);
    };
    element('employeeDashboard').dataset = {
        serverNow: options.now || '2026-09-03T15:00:00+07:00',
        checkoutAt: options.checkoutAt || '2026-09-03T16:00:00+07:00',
        timezone: 'Asia/Jakarta', challengeUrl: '/attendance/challenge'
    };
    element('checkInCard').disabled = !!options.disableCheckIn;
    element('checkOutCard').disabled = !!options.disableCheckOut;
    vm.runInNewContext(source, {
        document: { getElementById: element },
        window: { bootstrap: { Modal: class { show() { modalShows++; } } } },
        navigator: { geolocation: { getCurrentPosition(resolve, reject) {
            gpsCalls++;
            if (options.gpsError) reject({ code: 1 });
            else resolve({ coords: { latitude: -6.2, longitude: 106.8, accuracy: 10 }, timestamp: 1788422400000 });
        } } },
        fetch: async () => {
            fetchCalls++;
            return { ok: !options.challengeError, json: async () => ({ token: 'a'.repeat(64) }) };
        },
        performance: { now: () => elapsed },
        setInterval: fn => { intervals.push(fn); return intervals.length; },
        clearInterval() {}, setTimeout: () => 1, clearTimeout() {},
        AbortController, Intl, Date,
        HTMLFormElement: { prototype: { submit() { submitted.push(this.id); } } }
    });
    return {
        element, submitted, intervals,
        advance: ms => { elapsed += ms; intervals.forEach(fn => fn()); },
        counts: () => ({ gpsCalls, fetchCalls, modalShows }),
        flush: () => new Promise(resolve => setImmediate(resolve))
    };
}

test('check-in submits directly once, retaining the location and security nonce', async () => {
    const f = fixture();
    const first = f.element('checkInCard').fire('click');
    f.element('checkInCard').fire('click');
    await first;
    assert.deepEqual(f.submitted, ['checkInForm']);
    assert.deepEqual(f.counts(), { gpsCalls: 1, fetchCalls: 1, modalShows: 0 });
    assert.equal(f.element('checkInForm').elements.namedItem('attendance_nonce').value.length, 64);
    assert.equal(f.element('checkInForm').elements.namedItem('latitude').value, -6.2);
});

test('early checkout opens a modal without collecting GPS; blank reason cannot submit', async () => {
    const f = fixture();
    f.element('checkOutCard').fire('click');
    assert.equal(f.element('checkoutModal').classList.contains('is-early'), true);
    assert.equal(f.element('earlyReasonGroup').hidden, false);
    assert.equal(f.element('earlyCheckoutReason').required, true);
    f.element('earlyCheckoutReason').value = '   ';
    f.element('earlyCheckoutReason').fire('input');
    assert.equal(f.element('earlyReasonCounter').textContent, '3/1000');
    f.element('checkOutForm').fire('submit');
    await f.flush();
    assert.equal(f.counts().gpsCalls, 0);
    assert.deepEqual(f.submitted, []);
    let prevented = false;
    f.element('checkoutModal').fire('hide.bs.modal', { preventDefault() { prevented = true; } });
    assert.equal(prevented, false);
});

test('a filled early reason is trimmed and submitted after confirmation', async () => {
    const f = fixture();
    f.element('checkOutCard').fire('click');
    f.element('earlyCheckoutReason').value = '  Keperluan keluarga  ';
    f.element('checkOutForm').fire('submit');
    await f.flush();
    assert.deepEqual(f.submitted, ['checkOutForm']);
    assert.equal(f.element('earlyCheckoutReason').value, 'Keperluan keluarga');
});

test('normal checkout submits directly in one click without opening a modal', async () => {
    const f = fixture({ now: '2026-09-03T16:00:00+07:00' });
    f.element('checkOutCard').fire('click');
    await f.flush();
    assert.equal(f.element('checkoutModal').classList.contains('is-early'), false);
    assert.equal(f.element('earlyCheckoutReason').disabled, true);
    assert.equal(f.element('earlyCheckoutReason').required, false);
    assert.deepEqual(f.counts(), { gpsCalls: 1, fetchCalls: 1, modalShows: 0 });
    assert.deepEqual(f.submitted, ['checkOutForm']);
});

test('checkout failure moves keyboard focus to the checkout warning', async () => {
    const f = fixture({ now: '2026-09-03T16:00:00+07:00', gpsError: true });
    f.element('checkOutCard').fire('click');
    await f.flush();
    assert.equal(f.element('checkoutError').hidden, false);
    assert.equal(f.element('checkoutError').focused, true);
});

test('night shift at 23:00 still requires a reason until 07:00 the next day', () => {
    const f = fixture({ now: '2026-09-03T23:00:00+07:00', checkoutAt: '2026-09-04T07:00:00+07:00' });
    f.element('checkOutCard').fire('click');
    assert.equal(f.element('earlyCheckoutReason').required, true);
    f.element('checkoutModal').fire('shown.bs.modal');
    f.advance(8 * 60 * 60 * 1000);
    assert.equal(f.element('earlyCheckoutReason').required, false);
    assert.equal(f.element('confirmCheckout').textContent, 'Ya, Pulang');
});

for (const option of ['gpsError', 'challengeError']) {
    test(option + ' displays a warning and restores the original disabled state', async () => {
        const f = fixture({ [option]: true, disableCheckOut: true });
        await f.element('checkInCard').fire('click');
        assert.equal(f.element('attendanceError').hidden, false);
        assert.equal(f.element('checkInCard').disabled, false);
        assert.equal(f.element('checkOutCard').disabled, true);
        assert.deepEqual(f.submitted, []);
    });
}

test('disabled attendance cards make no requests or modal changes', async () => {
    const f = fixture({ disableCheckIn: true, disableCheckOut: true });
    await f.element('checkInCard').fire('click');
    f.element('checkOutCard').fire('click');
    assert.deepEqual(f.counts(), { gpsCalls: 0, fetchCalls: 0, modalShows: 0 });
});
