/**
 * Helper murni untuk menghitung fase, persentase progres, dan label penanda timeline jadwal.
 * Dipakai employee-schedule.jsx dan unit test frontend tanpa ketergantungan pada DOM atau backend.
 * Semua input waktu memakai timestamp penuh agar shift malam lintas tanggal dihitung dengan benar.
 */
const HOUR = 60 * 60 * 1000;

function timeline(now, startsAt, endsAt) {
    const start = Date.parse(startsAt);
    const end = Date.parse(endsAt);
    if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) return null;
    return {
        start, end,
        percent: Math.max(0, Math.min(100, (now - start) / (end - start) * 100)),
        phase: now < start ? 'Belum dimulai' : (now >= end ? 'Rentang jadwal berakhir' : 'Jadwal berlangsung')
    };
}

function markers(start, end, compact = false) {
    if (compact) return [start, start + (end - start) / 2, end];
    const step = Math.max(2, Math.ceil((end - start) / HOUR / 6 / 2) * 2) * HOUR;
    const ticks = [start];
    for (let at = start + step; at < end; at += step) ticks.push(at);
    // Avoid overlapping labels when the last partial interval is very short.
    if (ticks.length > 1 && end - ticks[ticks.length - 1] < step / 2) ticks.pop();
    ticks.push(end);
    return ticks;
}

module.exports = { timeline, markers };
