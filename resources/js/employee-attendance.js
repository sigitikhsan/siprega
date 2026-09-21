/**
 * Mengatur interaksi card absen masuk/pulang, pengambilan GPS, challenge nonce, dan modal alasan pulang awal.
 * Elemen serta URL berasal dari dashboard pegawai; keputusan akhir tetap divalidasi AttendanceController di server.
 * File ini sengaja berupa JavaScript ringan tanpa framework agar aksi absensi dapat dimuat cepat.
 */
(() => {
    'use strict';

    const dashboard = document.getElementById('employeeDashboard');
    if (!dashboard) return;

    const get = id => document.getElementById(id);
    const checkInCard = get('checkInCard');
    const checkOutCard = get('checkOutCard');
    const checkoutForm = get('checkOutForm');
    const modalElement = get('checkoutModal');
    const reason = get('earlyCheckoutReason');
    const confirmButton = get('confirmCheckout');
    const progress = get('attendanceProgress');
    const serverTime = Date.parse(dashboard.dataset.serverNow);
    const startedAt = performance.now();
    const now = () => serverTime + performance.now() - startedAt;
    const checkoutAt = Date.parse(dashboard.dataset.checkoutAt);

    let busy = false;
    let modal;
    const cards = [checkInCard, checkOutCard];
    const initialDisabled = cards.map(card => card.disabled);
    const dismissButtons = Array.from(modalElement.querySelectorAll('[data-bs-dismiss="modal"]'));

    function updateCheckoutModal() {
        const early = now() < checkoutAt;
        get('earlyReasonGroup').hidden = !early;
        reason.disabled = !early;
        reason.required = early;
        get('checkoutModalTitle').textContent = early ? 'Konfirmasi Absen Pulang Lebih Awal' : 'Konfirmasi Absen Pulang';
        get('checkoutModalDescription').textContent = early
            ? 'Jadwal pulang Anda ' + new Intl.DateTimeFormat('id-ID', {
                timeZone: dashboard.dataset.timezone, dateStyle: 'medium', timeStyle: 'short'
            }).format(new Date(checkoutAt)) + '. Silakan isi alasan pulang lebih awal.'
            : 'Catat waktu kepulangan Anda sekarang?';
        confirmButton.textContent = early ? 'Kirim & Absen Pulang' : 'Ya, Pulang';
    }

    function showError(target, message) {
        target.textContent = message;
        target.hidden = false;
        progress.textContent = message;
    }

    async function submitAttendance(form, card, action) {
        if (busy || card.disabled) return;
        const errorBox = action === 'check_out' ? get('checkoutError') : get('attendanceError');
        errorBox.hidden = true;
        if (!navigator.geolocation) {
            showError(errorBox, 'Perangkat atau browser ini tidak mendukung lokasi. Gunakan browser yang mendukung GPS.');
            return;
        }

        busy = true;
        cards.forEach(item => { item.disabled = true; });
        card.setAttribute('aria-busy', 'true');
        confirmButton.disabled = true;
        reason.readOnly = true;
        dismissButtons.forEach(button => { button.disabled = true; });
        const hint = card.querySelector('[data-card-hint]');
        const originalHint = hint.textContent;
        const originalLabel = confirmButton.textContent;
        const setProgress = message => {
            hint.textContent = message;
            progress.textContent = message;
            if (action === 'check_out') confirmButton.textContent = message;
        };
        setProgress('Mengambil lokasi...');

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, error => {
                    reject(new Error(error.code === 1
                        ? 'Izin lokasi ditolak. Aktifkan akses lokasi browser, lalu coba kembali.'
                        : 'Lokasi gagal diperoleh. Aktifkan GPS dan coba kembali.'));
                }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
            });

            setProgress('Memverifikasi absensi...');
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 15000);
            let token;
            try {
                const response = await fetch(dashboard.dataset.challengeUrl + '?action=' + encodeURIComponent(action), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin', cache: 'no-store', signal: controller.signal
                });
                if (!response.ok) throw new Error('challenge_failed');
                token = (await response.json()).token;
                if (typeof token !== 'string' || token.length !== 64) throw new Error('challenge_failed');
            } catch (error) {
                throw new Error('Verifikasi keamanan gagal. Periksa koneksi atau muat ulang halaman, lalu coba kembali.');
            } finally {
                clearTimeout(timeout);
            }

            const values = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                captured_at: new Date(position.timestamp).toISOString(),
                attendance_nonce: token
            };
            Object.keys(values).forEach(name => { form.elements.namedItem(name).value = values[name]; });
            setProgress('Menyimpan absensi...');
            // Final time, location, schedule and early-reason validation remains on the server.
            HTMLFormElement.prototype.submit.call(form);
        } catch (error) {
            busy = false;
            cards.forEach((item, index) => { item.disabled = initialDisabled[index]; });
            card.removeAttribute('aria-busy');
            confirmButton.disabled = false;
            reason.readOnly = false;
            dismissButtons.forEach(button => { button.disabled = false; });
            hint.textContent = originalHint;
            confirmButton.textContent = originalLabel;
            showError(errorBox, error.message || 'Absensi gagal diproses. Silakan coba kembali.');
            if (action === 'check_in') errorBox.focus();
        }
    }

    checkInCard.addEventListener('click', () => submitAttendance(get('checkInForm'), checkInCard, 'check_in'));
    checkOutCard.addEventListener('click', () => {
        if (busy || checkOutCard.disabled) return;

        // Once the scheduled checkout time has arrived, one click is enough.
        // The server still verifies the time and requires a reason if the client clock is misleading.
        if (Number.isFinite(checkoutAt) && now() >= checkoutAt) {
            reason.value = '';
            reason.disabled = true;
            reason.required = false;
            submitAttendance(checkoutForm, checkOutCard, 'check_out');
            return;
        }

        if (!window.bootstrap || !window.bootstrap.Modal) {
            showError(get('attendanceError'), 'Form konfirmasi belum dapat dimuat. Periksa koneksi lalu muat ulang halaman.');
            return;
        }
        get('checkoutError').hidden = true;
        updateCheckoutModal();
        modal = modal || new window.bootstrap.Modal(modalElement);
        modal.show();
    });
    modalElement.addEventListener('shown.bs.modal', () => {
        (reason.required ? reason : confirmButton).focus();
    });
    modalElement.addEventListener('hide.bs.modal', event => {
        if (busy) event.preventDefault();
    });
    modalElement.addEventListener('hidden.bs.modal', () => checkOutCard.focus());
    setInterval(() => {
        if (!busy && modalElement.classList.contains('show')) updateCheckoutModal();
    }, 1000);
    reason.addEventListener('input', () => reason.setCustomValidity(''));
    checkoutForm.addEventListener('submit', event => {
        event.preventDefault();
        if (busy || checkOutCard.disabled) return;
        updateCheckoutModal();
        reason.value = reason.value.trim();
        reason.setCustomValidity(reason.required && !reason.value ? 'Tuliskan alasan pulang lebih awal.' : '');
        if (!checkoutForm.reportValidity()) return;
        submitAttendance(checkoutForm, checkOutCard, 'check_out');
    });
})();
