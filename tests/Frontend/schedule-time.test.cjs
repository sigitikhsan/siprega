const { test } = require('node:test');
const assert = require('node:assert/strict');
const { timeline, markers } = require('../../resources/js/schedule-time');

const start = '2026-09-10T05:00:00+07:00';
const end = '2026-09-10T16:00:00+07:00';
test('progress is clamped before and after a schedule', () => {
    assert.equal(timeline(Date.parse(start) - 1000, start, end).percent, 0);
    assert.equal(timeline(Date.parse(end) + 1000, start, end).percent, 100);
    assert.equal(timeline(Date.parse('2026-09-10T10:30:00+07:00'), start, end).percent, 50);
});
test('a night shift crosses midnight using full dates', () => {
    const night = timeline(Date.parse('2026-09-11T01:00:00+07:00'), '2026-09-10T19:00:00+07:00', '2026-09-11T07:00:00+07:00');
    assert.equal(night.percent, 50);
    assert.equal(night.phase, 'Jadwal berlangsung');
});
test('old open attendance finishes its range without resetting the clock', () => {
    assert.equal(timeline(Date.parse('2026-09-15T10:00:00+07:00'), start, end).phase, 'Rentang jadwal berakhir');
});
test('missing, inverted, or empty ranges have no progress', () => {
    assert.equal(timeline(Date.now(), null, null), null);
    assert.equal(timeline(Date.now(), end, start), null);
    assert.equal(timeline(Date.now(), start, start), null);
});
test('desktop tick labels include endpoints and never exceed seven', () => {
    const from = Date.parse(start);
    for (const hours of [1, 5, 11, 12, 13, 17, 24]) {
        const to = from + hours * 3600000;
        const ticks = markers(from, to);
        assert.equal(ticks[0], from);
        assert.equal(ticks[ticks.length - 1], to);
        assert.ok(ticks.length <= 7);
    }
    assert.equal(markers(from, Date.parse(end)).length, 7);
});
test('mobile tick labels are only start, midpoint, and end', () => {
    const from = Date.parse(start), to = Date.parse(end);
    assert.deepEqual(markers(from, to, true), [from, (from + to) / 2, to]);
});
