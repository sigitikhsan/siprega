/**
 * Komponen React untuk ticker jam dan progres rentang jadwal pada dashboard pegawai.
 * Menerima snapshot waktu/jadwal dari DashboardController dan memakai helper schedule-time untuk kalkulasi visual.
 * Ticker bukan sumber keputusan absensi; validasi waktu, shift, izin, dan libur tetap dilakukan server.
 */
import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { timeline, markers } from './schedule-time';
import '../css/employee-schedule.css';

const rootElement = document.getElementById('employeeScheduleTicker');

const stateMessages = {
    day_off: ['Hari ini libur', 'Tidak ada rentang jadwal kerja untuk hari ini.'],
    sick: ['Sakit · Disetujui', 'Pengajuan sakit Anda telah disetujui.'],
    leave: ['Izin · Disetujui', 'Pengajuan izin Anda telah disetujui.'],
    unavailable: ['Tidak ada jadwal aktif', 'Hubungi administrator untuk informasi jadwal Anda.']
};
const shiftLabels = { fixed: 'Tetap', day: 'Shift Siang', night: 'Shift Malam' };

function ScheduleTicker({ data, anchor }) {
    const currentTime = () => anchor.server + performance.now() - anchor.monotonic;
    const [now, setNow] = useState(currentTime);
    useEffect(() => {
        const update = () => setNow(currentTime());
        const timer = window.setInterval(update, 1000);
        document.addEventListener('visibilitychange', update);
        return () => {
            window.clearInterval(timer);
            document.removeEventListener('visibilitychange', update);
        };
    }, []);

    const format = (value, options) => new Intl.DateTimeFormat('id-ID', {
        timeZone: data.timezone, ...options
    }).format(new Date(value));
    const time = value => format(value, { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).replace('.', ':');
    const tickLabel = value => time(value).replace(/:00$/, '');
    const progress = data.state === 'scheduled' ? timeline(now, data.startsAt, data.endsAt) : null;
    const message = stateMessages[data.state] || stateMessages.unavailable;
    const zone = { 'Asia/Jakarta': 'WIB', 'Asia/Makassar': 'WITA', 'Asia/Jayapura': 'WIT' }[data.timezone] || data.timezone;
    const crossesDate = progress && format(progress.start, { day: 'numeric', month: 'numeric', year: 'numeric' }) !==
        format(progress.end, { day: 'numeric', month: 'numeric', year: 'numeric' });

    const renderTicks = compact => (
        <div className={'schedule-ticker__ticks' + (compact ? ' schedule-ticker__ticks--compact' : ' schedule-ticker__ticks--wide')} aria-hidden="true">
            {markers(progress.start, progress.end, compact).map(at => (
                <span key={at} className={'schedule-ticker__tick' + (at <= now ? ' is-passed' : '')}
                    style={{ left: ((at - progress.start) / (progress.end - progress.start) * 100) + '%' }}>
                    <span className="schedule-ticker__dot" />
                    <span>{tickLabel(at)}</span>
                </span>
            ))}
        </div>
    );

    return (
        <div className="schedule-ticker">
            <div className="schedule-ticker__header">
                <div className="schedule-ticker__identity">
                    <span className="schedule-ticker__icon" aria-hidden="true">
                        <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">
                            <rect x="3" y="5" width="18" height="16" rx="3" /><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2" />
                        </svg>
                    </span>
                    <div className="schedule-ticker__heading">
                        <div className="schedule-ticker__name-row">
                            <h2>{data.scheduleName || 'Informasi Kerja'}</h2>
                            {data.shiftType && <span className="schedule-ticker__badge">{shiftLabels[data.shiftType] || 'Jadwal'}</span>}
                        </div>
                        <p>{format(Date.parse(data.shiftDate), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })} · Tanggal shift</p>
                    </div>
                </div>
                <div className="schedule-ticker__clock" aria-label="Waktu saat ini">
                    <span className="schedule-ticker__clock-label"><span /> Waktu sekarang</span>
                    <div><time dateTime={new Date(now).toISOString()}>{format(now, { hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }).replace(/\./g, ':')}</time><span className="schedule-ticker__zone">{zone}</span></div>
                </div>
            </div>

            {progress ? (
                <div className="schedule-ticker__timeline">
                    {renderTicks(false)}
                    {renderTicks(true)}
                    <div className="schedule-ticker__track" role="progressbar" aria-label="Progres rentang jadwal"
                        aria-valuemin="0" aria-valuemax="100" aria-valuenow={Math.round(progress.percent)}
                        aria-valuetext={progress.phase + ', ' + Math.round(progress.percent) + '% rentang jadwal'}>
                        <div className="schedule-ticker__fill" style={{ width: progress.percent + '%' }} />
                    </div>
                    <div className="schedule-ticker__endpoints">
                        <span>Absen dibuka <strong>{time(progress.start)}</strong></span>
                        <span>Jadwal pulang <strong>{time(progress.end)}{crossesDate ? ' (+1 hari)' : ''}</strong></span>
                    </div>
                    <div className="schedule-ticker__footer">
                        <span>{progress.phase}</span>
                        <span>{data.attendanceStatus}</span>
                    </div>
                </div>
            ) : (
                <div className="schedule-ticker__empty">
                    <strong>{message[0]}</strong><span>{message[1]}</span>
                </div>
            )}
        </div>
    );
}

if (rootElement) {
    const data = JSON.parse(rootElement.dataset.schedule);
    const anchor = { server: Date.parse(data.serverNow), monotonic: performance.now() };
    createRoot(rootElement).render(<ScheduleTicker data={data} anchor={anchor} />);
}
