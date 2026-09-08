# Security Review — SiPrega (Sistem Presensi Pegawai Balmon)

**Tanggal audit:** 03 September 2026
**Lingkup:** `D:\Laravel\absensi-pegawai`
**Stack:** Laravel 8.83.29 · PHP 7.4.33 · MySQL · maatwebsite/excel 3.1
**Note:** Audit bersifat read-only. Tidak ada file yang diubah. Tidak ada migration/seeder yang dijalankan. Secret dari `.env` tidak ditampilkan.

---

## A. Executive Summary

Sistem presensi ini secara umum **dibangun dengan kesadaran keamanan yang baik**. Fondasi yang kuat sudah ada: identificasi IDOR dicegah pada hampir semua route pegawai, proteksi CSRF aktif, input divalidasi dengan parameter binding (bebas SQLi), output di-escape di seluruh Blade (`{{ }}`) dan dibangun via `textContent` pada polling (bebas XSS), rate limiting login (per-akun & per-IP), lock pada transaksi untuk mencegah double-submission, nonce challenge GPS sekali pakai yang terikat user+aksi, dan escape formula injection pada ekspor Excel. Terdapat pula rangkaian test keamanan yang cukup komprehensif.

Namun ada **satu risiko arsitektural yang dominan**: proyek berjalan di **Laravel 8.83.29 + PHP 7.4.33 yang sudah End-of-Life (EOL)** dan **tidak akan lagi menerima patch keamanan**. `composer audit` menemukan 3 advisory (termasuk **file validation bypass CVE-2025-27515** yang relevan langsung dengan fitur upload lampiran izin/sakit, dan CRLF injection pada rule `email`). Karena versi ini tidak lagi di-support, kerentanan tersebut tidak akan diperbaiki oleh upstream.

Faktor penentu kedua adalah **konfigurasi produksi yang belum disiapkan**: `.env` saat ini `APP_ENV=local` + `APP_DEBUG=true` + `SESSION_SECURE_COOKIE=false`, dan `TrustHosts` dinonaktifkan. Ini semua harus diperbaiki sebelum pemasangan di server kantor.

### Risiko Utama
1. **Framework + runtime EOL dengan unpatched CVEs yang menyentuh fitur aktif (upload file).**
2. **Konfigurasi produksi belum siap** (debug on, cookie secure off, trusted hosts off).
3. **Endpoint ekspor Excel tanpa batas tanggal wajib dan tanpa rate limit** → potensi eksfiltrasi seluruh data / heavy query.
4. **Beban query pada dashboard live** (polling 15 detik × banyak admin) tanpa cache/index optimal.

### Kesiapan Deployment
**~60%** — perkiraan sementara.

Alasan: fondasi keamanan aplikasi (auth, CSRF, otorisasi, GPS challenge, locking) sudah solid dan teruji, sehingga sisa pekerjaan 40% berpusat pada (a) keputusan framework EOL — upgrade atau isolasi risiko, (b) konfigurasi produksi/hardening env, dan (c) pengamanan endpoint ekspor & dashboard. Item P0 (halaman D) tergolong cepat dieksekusi.

---

## B. Temuan

### B.1 Framework & Dependensi EOL (Confirmed — Critical)

- **ID:** F-01
- **Severity:** Critical
- **Status:** Confirmed
- **Judul:** Laravel 8.83.29 (EOL) + PHP 7.4.33 (EOL) tanpa jalur patch keamanan upstream
- **File:** `composer.json:15` (`"laravel/framework": "^8.0"`), `composer.json:11` (`"php": "^7.3"`); hasil `composer audit`
- **Bukti / alur eksploitasi:** `composer audit` melaporkan 3 advisory pada `laravel/framework` yang memengaruhi versi ini (Laravel 8 < 10.48.29) dan **tidak akan pernah di-patch**:
  - **PKSA-3r5d (High) — CRLF injection pada default `email` rule** (GHSA-5vg9-5847-vvmq). Aplikasi memvalidasi `email` di `EmployeeController:140`, `Admin/ProfileController:36`, `Employee/ProfileController:33`.
  - **PKSA-8qx3 / CVE-2025-27515 (Medium) — file validation bypass** pada rule `mimes`/`image`. Aplikasi **menggunakan `mimes:jpg,jpeg,png,pdf`** pada upload lampiran di `Employee/LeaveRequestController:39` → berpotensi bisa dilewati.
  - **PKSA-m5cs (Medium) — signed URL path confusion** (aplikasi tidak memakai signed URL, dampak rendah).
  - PHP 7.4.33 EOL sejak akhir 2022; Laravel 8 EOL sejak 8 Feb 2023.
- **Dampak:** Kerentanan validasi file & email pada framework yang aktif dipakai fiturnya, tanpa jalur perbaikan dari vendor. User dengan akun employee bisa memanfaatkan bypass validasi file untuk mengunggah konten berbahaya sebagai lampiran.
- **Rekomendasi:** **P0 — upgrade** ke Laravel LTS (minimal 10.x, sebaiknya 11/12) dan PHP ≥ 8.1/8.2 **sebelum deployment**. Jika upgrade tidak memungkinkan dalam waktu dekat: mitigasi sementara dengan validasi MIME berbasis *content sniffing* (mis. `finfo`/`getimagesize`), whitelist ekstensi ganda, dan hindari mengandalkan rule `mimes` bawaan; pastikan lampiran hanya disajikan sebagai `attachment` (sudah) dan tidak pernah dieksekusi.
- **Test yang disarankan:** (P0) Test bypass `mimes` dengan file polyglot/HTML bernama `.jpg`; test header injection pada kolom email.

### B.2 Konfigurasi Produksi (Debug) (Potential — High)

- **ID:** F-02
- **Severity:** High
- **Status:** Potential (deployment config)
- **Judul:** `APP_DEBUG=true` dan `APP_ENV=local` berpotensi bocor ke produksi
- **File:** `.env` (lokal) — `APP_ENV=local`, `APP_DEBUG=true`; referensi `config/app.php:29,42`
- **Bukti / alur eksploitasi:** Jika `.env` produksi tidak diubah, halaman error menampilkan stack trace penuh, path server, versi paket, dan potensi nilai env. Dengan `(bool) env('APP_DEBUG', false)` di `config/app.php:42`, debug aktif ketika `APP_DEBUG=true`.
- **Dampak:** Pengungkapan informasi sensitif (path, versi, konfigurasi) kepada attacker; mempercepat rekayasa exploit.
- **Rekomendasi:** **P0** — di produksi set `APP_ENV=production` dan `APP_DEBUG=false`.
- **Test yang disarankan:** Test `config('app.env') === 'production'` dan `config('app.debug') === false` (AssertableEnv/komponen `Environment`).

### B.3 Ekspor Excel — Tanpa Batas Tanggal Wajib & Tanpa Rate Limit (Confirmed — High)

- **ID:** F-03
- **Severity:** High
- **Status:** Confirmed
- **Judul:** Endpoint `/admin/attendances/export` & `/admin/attendance-recap/export` dapat mengekspor seluruh data tanpa rentang tanggal dan tanpa throttle
- **File:** `routes/web.php:45,47`; `app/Http/Controllers/Admin/AttendanceController.php:47-53`; `app/Exports/AttendanceRecapExport.php:22-34`
- **Bukti / alur eksploitasi:** `validateFilters()` (`AttendanceController:55-75`) membuat `date_from`/`date_to` bersifat `nullable`, sehingga GET `admin/attendances/export` tanpa parameter mengekspor **semua baris di tabel `attendances`**. Endpoint tidak memakai middleware `throttle`, dan `AttendanceRecapExport::sheets()` (8 sheet) menjalankan 8 query penuh. Struktur 8 sheet berarti 8× pemrosesan dataset besar.
- **Dampak:** (1) Eksfiltrasi seluruh riwayat absensi lintas semua pegawai/periode oleh admin (data breach internal); (2) heavy query / memory hingga **Denial of Service** pada server kantor; (3) waktu respons lama.
- **Rekomendasi:** **P0** — wajibkan `date_from` + `date_to` pada semua route export (validasi `required`), batasi rentang maksimum (mis. 31 hari), tambahkan `throttle`, dan pertimbangkan antrian (queue) untuk export besar. Batasi jumlah sheet bila tidak diperlukan.
- **Test yang disarankan:** Test export tanpa tanggal → ditolak; test rentang > maksimum → ditolak; test `throttle` pada export; test otorisasi (hanya admin).

### B.4 Dashboard Live — Beban Query & Tanpa Cache (Confirmed — Medium)

- **ID:** F-04
- **Severity:** Medium
- **Status:** Confirmed
- **Judul:** `/admin/dashboard/live` menjalankan query berat setiap 15 detik per admin tanpa cache dan tanpa index optimal
- **File:** `routes/web.php:35-37`; `app/Http/Controllers/Admin/DashboardController.php:36-70,72-119`; `resources/views/admin/dashboard.blade.php:290`
- **Bukti / alur eksploitasi:** Setiap polling `live()` memanggil `statistics()` (4 COUNT queries), `recentAttendances()`, `recentLeaveRequests()`, dan tiap 4× polling `attendanceChart()` yang memuat **semua baris attendance 7 hari** via `whereDate('attendance_date', '>=', ...)` (`DashboardController:99-104`) lalu meng-group di PHP. Polling browser berinterval 15 detik (`dashboard.blade.php:290`). Dengan N admin membuka dashboard, terjadi ~N query-Count × 4 + N × (7-hari scan) per 15 detik. Kolom `attendance_date` hanya bagian dari index komposit `(employee_id, attendance_date)` sehingga scan `whereDate` tanpa `employee_id` tidak memakai index secara efisien. Route sudah `throttle:12,1` (baik) dan `admin` (baik) — lihat temuan positif.
- **Dampak:** Degradasi performa / DoS intermitten saat banyak admin aktif; peningkatan beban DB server kantor.
- **Rekomendasi:** **P1** — tambahkan index terpisah pada `attendance_date` (atau combined yang cocok untuk `whereDate`), cache agregat (mis. Redis/file, TTL 10–30 detik), atau hitung chart dengan query agregat GROUP BY; kurangi interval polling. Data yang dikirim sudah netral (nama/lokasi/waktu), risiko kebocoran rendah.
- **Test yang disarankan:** Test batas throttle; test bahwa response tidak mengandung data sensitif (mis. absen lengkap lintas pegawai) selain yang ditampilkan di UI.

### B.5 Cookie Session — Kurang Flag Secure di Produksi (Confirmed — Medium)

- **ID:** F-05
- **Severity:** Medium
- **Status:** Confirmed (deployment config)
- **Judul:** `SESSION_SECURE_COOKIE=false` dan tanpa `AuthenticateSession`, cookie session tidak ber-flag Secure saat produksi HTTPS
- **File:** `config/session.php:171` (`'secure' => env('SESSION_SECURE_COOKIE', false)`); `.env` `SESSION_SECURE_COOKIE=false`; `app/Http/Kernel.php:37` (`AuthenticateSession` dikomentari)
- **Bukti / alur eksploitasi:** Di lingkungan produksi HTTPS, jika `SESSION_SECURE_COOKIE` tidak di-set `true`, session cookie dapat dikirim melalui koneksi tidak terenkripsi → berpotensi dicegat (session hijacking) saat ada jalur HTTP. Saat ini `.env` lokal `false` (tidak masalah untuk dev, tapi berisiko jika dibawa ke prod). `AuthenticateSession` nonaktif berarti tidak ada verifikasi remember/aktivasi ulang per-request, dan tidak ada logout berbasis session-id lama di request berikutnya (pelengkap session fixation defense).
- **Dampak:** Risiko session hijacking pada kontrol akses admin; hardening session tidak lengkap.
- **Rekomendasi:** **P0/P1** — produksi set `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax|strict`, pastikan HTTPS-only (baik melalui env maupun kebijakan server). **P1** — nyalakan `\Illuminate\Session\Middleware\AuthenticateSession::class` di group `web` (menambah lapisan verifikasi session + rotasi remember token aktif).
- **Test yang disarankan:** Test bahwa cookie session hanya dikirim via HTTPS (Secure) di lingkungan prod; test bahwa mengubah password/logout dari satu device membatalkan device lain (bila AuthenticateSession diaktifkan).

### B.6 File Upload — MIME Spoofing & Validasi Bawaan Tidak Cukup (Potential — Medium)

- **ID:** F-06
- **Severity:** Medium
- **Status:** Potential
- **Judul:** Lampiran izin/sakit hanya divalidasi ekstensi `mimes`; konten tidak diperiksa di sisi server (dan diperparah oleh CVE-2025-27515 F-01)
- **File:** `app/Http/Controllers/Employee/LeaveRequestController.php:39,51-55,79-84`
- **Bukti / alur eksploitasi:** Rule `'mimes:jpg,jpeg,png,pdf'` + `max:5120`. `$file->getClientOriginalExtension()` (`:54`) diambil langsung dari nama file user untuk menyusun nama tersimpan (UUID + ekstensi user). Tidak ada validasi magic-byte (`finfo`/`getimagesize`). Karena `download()` (`:83`) menyajikan file via `Storage::download` (header `Content-Disposition: attachment`), eksekusi di server tidak terjadi, dan ekstensi dibatasi whitelist → risiko rendah namun nyata untuk file polyglot/berbahaya yang lolos `mimes` (utamanya pada framework EOL, lihat F-01).
- **Dampak:** Lampiran berisi konten berbahaya (mis. HTML/JS phising, file terinfeksi) dapat disimpan & didistribusikan ke admin; pengungkapan jika disajikan inline. Ekstensi user-controlled pada nama file.
- **Rekomendasi:** **P1** — validasi magic bytes aktual (`finfo`) dan jangan mengandalkan `getClientOriginalExtension()` untuk nama file (gunakan ekstensi hasil deteksi konten yang di-whitelist); pastikan penyajian selalu `attachment`; pertimbangkan scan antivirus; batasi ekstensi ganda.
- **Test yang disarankan:** Test upload dengan konten HTML ber-ekstensi `.jpg` → ditolak; test ekstensi ganda `x.jpg.php`; test bahwa download selalu `Content-Disposition: attachment`.

### B.7 Manajemen Proksi & Host yang Tidak Dikonfigurasi (Hardening — Medium)

- **ID:** F-07
- **Severity:** Medium
- **Status:** Hardening
- **Judul:** `TrustHosts` dinonaktifkan & `TrustProxies` belum diarahkan ke proxy/load-balancer produksi
- **File:** `app/Http/Kernel.php:17` (`TrustHosts` dikomentari); `app/Http/Middleware/TrustProxies.php:15` (`$proxies` kosong)
- **Bukti / alur eksploitasi:** `TrustHosts` tidak dalam middleware global → aplikasi membangun URL berdasarkan `Host` header sesuka attacker (Host-header poisoning). `TrustProxies` dengan `$proxies` kosong akan me-trust hanya IP koneksi langsung (default aman), tetapi **belum di-set ke IP load-balancer/reverse-proxy** sehingga `X-Forwarded-For`/`HTTPS` tidak diverifikasi di luar proxy — hal ini dapat memicu: (1) saat di belakang proxy, `$request->ip()` mengembalikan IP proxy bukan IP klien → **rate limiting login/IP menjadi tidak efektif/bisa dimanipulasi**; (2) `isSecure()` salah bila reverse-proxy menangani TLS.
- **Dampak:** Rate limiting berbasis IP (lihat F-09 yang sudah baik) dapat dikaburkan di belakang proxy; potensi poisoning URL; keputusan `isSecure` keliru.
- **Rekomendasi:** **P1** — aktifkan `TrustHosts` dengan pola host resmi; set `TrustProxies::$proxies` ke IP/rentang load-balancer sebenarnya (jangan `*`) di produksi; pastikan `X-Forwarded-Proto` dipercaya agar `secure`/`isSecure` benar.
- **Test yang disarankan:** Test Host-header injection (tidak menghasilkan redirect/konten berdasarkan host tak dikenal); test `request()->ip()` benar di belakang proxy (Dusk/feature dengan header XFF).

### B.8 Otorisasi API & Guard Token (Informational — Low)

- **ID:** F-08
- **Severity:** Low
- **Status:** Informational
- **Judul:** Guard `api` (token, `hash:false`) dan route `/api/user` tidak memiliki mekanisme penerbitan token yang aktif
- **File:** `config/auth.php:47-51`; `routes/api.php:17-19`; `app/Http/Kernel.php:43-46`
- **Bukti / alur eksploitasi:** Guard `api` memakai driver `token` dengan `hash => false`. Tidak ada kolom `api_token` di migrasi `2026_08_13_023749_create_users_table.php` dan tidak ada endpoint yang menerbitkan token. Route `/api/user` (protect `auth:api`) praktis tidak dapat dilalui, dan `api` group hanya punya `throttle:api` (default). Dampak langsung sangat rendah karena tidak ada konsumen API.
- **Dampak:** Permukaan serangan API potensial jika kelak ditambahkan; konfigurasi mati yang membingungkan.
- **Rekomendasi:** **P1** — jika API tidak diperlukan, hapus guard/route `/api/user` & CORS path `api/*`, atau lengkapi dengan autentikasi stateless yang aman (Sanctum) bila dibutuhkan. Set `supports_credentials`/`allowed_origins` sesuai kebutuhan.
- **Test yang disarankan:** Test bahwa `/api/user` tidak autentik (401); (opsional) audit CORS origin.

### B.9 Positif pada Rate Limiting (dikutip sebagai dasar, lihat C) — tidak ada temuan negatif pada login.

### B.10 GPS/Attendance — Manipulasi Koordinat oleh Klien (Confirmed — High residual risk, by design)

- **ID:** F-11
- **Severity:** High (residual/by-design)
- **Status:** Confirmed
- **Judul:** Posisi GPS (latitude/longitude/accuracy) sepenuhnya dikendalikan klien; fake-GPS / request manual dapat mengklaim berada di dalam radius
- **File:** `app/Http/Controllers/Employee/AttendanceController.php:149-171,195-213`; `resources/views/employee/dashboard.blade.php:119-176`
- **Bukti / alur eksploitasi:** `validatePosition()` (`:151-156`) hanya memvalidasi range numerik; `resolveLocation()` (`:195-213`) menghitung jarak dari koordinat **yang dikirim user** dan menerima jika dalam radius + akurasi cukup. Tidak ada mekanisme independen (mis. cell-tower/Wi-Fi, virus pada `captured_at` vs sinyal radio, foto geotag) untuk membuktikan perangkat benar-benar berada di lokasi. Dengan fake-GPS app, DevTools console, atau Postman, seorang employee yang memiliki session valid dapat mengirim koordinat lokasi kantor dari mana saja — didukung challenge nonce (yang memang bisa mereka minta sendiri). Mitigasi yang ada (cek ketahanan `captured_at` 2–1 menit; `locationRisk` kecepatan antar-absensi) membantu tapi **tidak menghilangkan** spoofing titik tunggal.
- **Dampak:** Absensi GPS dapat dimanipulasi (check-in palsu dari luar kantor), mengancam integritas data presensi.
- **Rekomendasi:** **P1** — dokumentasikan sebagai risiko residual; perkuat dengan: (a) tandai semua absensi yang masuk via koordinat klien sebagai "perlu verifikasi manual"; (b) deteksi anomali lintas-device (kecepatan antar kehadiran — sudah ada), (c) pertimbangkan sertifikat lokasi/QR statis di lokasi + `captured_at` terikat, (d) tampilkan data GPS mentah ke admin untuk audit (sudah ada di `show`). Jangan klaim akurasi absolut.
- **Test yang disarankan:** Test upload koordinat palsu dalam radius → berhasil (konfirmasi residual risk) & usulkan fitur verifikasi manual; test `captured_at` di masa depan/masa lalu ekstrem ditolak.

### B.11 Session Fixation & Aktivasi Ulang (Hardening — Medium)

- **ID:** F-12
- **Severity:** Medium
- **Status:** Hardening
- **Judul:** `AuthenticateSession` nonaktif; tidak ada invalidation session lintas-perangkat saat password berubah untuk pegawai yang sedang aktif
- **File:** `app/Http/Kernel.php:37`; `app/Http/Controllers/Admin/ProfileController.php:64-70`; `app/Http/Controllers/Employee/ProfileController.php:60-66`
- **Bukti / alur eksploitasi:** Session diregenerate saat login (`LoginController:58`) — bagus. Namun setelah **ganti password**, sesi aktif di perangkat lain **tidak** dibatalkan (remember token dirotasi, tetapi session file/DB lama yang sudah autentik tetap valid hingga lifetime). Tanpa `AuthenticateSession`, `$request->session()->migrate()` pada `ProfileController` hanya memengaruhi sesi saat ini.
- **Dampak:** Perangkat terdahulu yang dicuri/dipakai orang lain tetap memiliki akses setelah korban mengganti password.
- **Rekomendasi:** **P1/P2** — aktifkan `AuthenticateSession`; dan/atau tambahkan `user_id` → `session` (database/redis) dan hapus sesi aktif user saat password diubah; pertimbangkan paksa re-login global.
- **Test yang disarankan:** Feature: login di 2 sesi, ganti password di sesi A, sesi B harus ditolak/401 pada request berikutnya (setelah AuthenticateSession aktif).

### B.12 Data Integrity & Indeks (Confirmed — Medium/Low)

- **ID:** F-13
- **Severity:** Medium
- **Status:** Confirmed
- **Judul:** Sebagian konsistensi shift/absensi/izin hanya di-enforce di lapisan aplikasi; tidak ada index optimal untuk query rentang tanggal & dashboard
- **File:** `database/migrations/2026_08_13_032450_create_attendances_table.php:60` (unique `(employee_id, attendance_date)`); `database/migrations/2026_08_29_100000_add_simple_shift_scheduling.php:23` (unique `(employee_id, shift_date)`); query `whereDate` di `DashboardController`, `Admin/AttendanceController`, `Employee/AttendanceHistoryController`
- **Bukti / alur eksploitasi:** Aturan "shift tidak boleh diubah setelah ada absensi", "izin tidak boleh disetujui bila ada absensi", "tidak boleh absen saat libur/izin disetujui" hanya di enforce di controller dalam transaksi (mis. `ShiftAssignmentController:54-69`, `Admin/LeaveRequestController:89-101`, `AttendanceController:42-48`). Ini baik, tetapi bergantung pada semua path memanggilnya. Belum ada index terpisah pada `attendance_date`, `start_date`, `reviewed_at` untuk query range/filter (terbatas pada index komposit). Penghapusan pegawai/jadwal yang berhistori sudah ditangani dengan FK `onDelete('restrict')` dan logika soft-deactivate (`EmployeeController:111-131`, `WorkScheduleController:75-93`) — positif.
- **Dampak:** Degradasi performa saat data besar; risiko inkonsistensi jika ada path baru yang mengubah data tanpa melalui cek (defense-in-depth terbatas).
- **Rekomendasi:** **P1** — tambahkan index pada `attendance_date`, `leave_requests.start_date`, `leave_requests.reviewed_at`, `employee_shift_assignments.shift_date`, `attendances.check_in`; pertimbangkan DB constraint/migration guard untuk konsistensi kritis bila memungkinkan.
- **Test yang disarankan:** Test bahwa koreksi/shift/izin yang bertentangan ditolak di semua jalur (sebagian sudah ada); test performa query rentang dengan dataset besar.

### B.13 Informasi Sensitif dalam Log (Hardening — Low)

- **ID:** F-14
- **Severity:** Low
- **Status:** Hardening
- **Judul:** Log level `debug` untuk channel `single` dan belum ada redaction konfigurasi/env
- **File:** `config/logging.php:47` (`'level' => 'debug'`), `config/logging.php:52` (`daily` `debug`)
- **Bukti / alur eksploitasi:** Channel log default berada di level `debug`, yang di beberapa skenario bisa mencatat query/exception detail. Pastikan log produksi tidak mengekspos credential/`APP_KEY`/nomor WhatsApp (yang ada di `.env`). Tidak ditemukan logging kredensial secara eksplisit di kode, tapi `LOG_CHANNEL=stack` + `single` di level debug memperluas jejak.
- **Dampak:** Potensi kebocoran info sensitif ke file log yang mungkin bocor.
- **Rekomendasi:** **P2** — produksi set level log ke `warning`/`error`; pastikan storage/log tidak dapat diakses publik; audit log tidak boleh memuat password/PII tak perlu.
- **Test yang disarankan:** Test tidak ada response error yang menyertakan stack trace/credential saat `APP_DEBUG=false`; lint log untuk format secret.

### B.14 CSP Melemah oleh `unsafe-inline` (Hardening — Low)

- **ID:** F-15
- **Severity:** Low
- **Status:** Hardening
- **Judul:** Content-Security-Policy membolehkan `script-src 'unsafe-inline'` dan CDN `jsdelivr`
- **File:** `app/Http/Middleware/SecurityHeaders.php:14-17`
- **Bukti / alur eksploitasi:** `script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net` — karena semua script inline dipakai (dashboard, dsb.), `'unsafe-inline'` melemahkan nilai proteksi XSS dari CSP. CDN eksternal menambah permukaan supply-chain. (Ini kompensasi bundle, bukan bug.)
- **Dampak:** Jika ada XSS di masa depan, CSP tidak membatasi eksekusi inline.
- **Rekomendasi:** **P2** — pindahkan script inline ke file terpisah/materialize dengan nonce/hash, hilangkan `'unsafe-inline'` untuk `script-src`; idealnya self-host bootstrap/Chart.js.
- **Test yang disarankan:** Test header CSP memuat `script-src` tanpa `unsafe-inline` (setelah refactor); test halaman berfungsi.

### B.15 Kebijakan Password & Akun (Hardening — Low)

- **ID:** F-16
- **Severity:** Low
- **Status:** Hardening
- **Judul:** Tidak ada kebijakan kompleksitas/locked-out setelah lama tak aktif; tidak ada password reset via email (belum ada fitur)
- **File:** `config/auth.php:102` (expire 60/throttle 60 untuk reset — tapi tidak ada route reset); `LoginController`
- **Bukti / alur eksploitasi:** Reset password hanya via admin mengubah password pegawai (`EmployeeController:94-97`) — ini sah untuk manajemen terpusat. Namun password reset mandiri (lupa password) tidak tersedia. Rate limiting login sudah kuat. Tidak ada paksa-expire password.
- **Dampak:** Jika admin lupa password, tidak ada jalur self-recovery (operasional); password statis dalam jangka panjang.
- **Rekomendasi:** **P2** — pertimbangkan self-service reset dengan email kantor (dan ikuti praktik aman token); atau mekanisme admin recovery yang terdokumentasi; evaluasi kebijakan expiry password.
- **Test yang disarankan:** Test bahwa reset password meng-rotate remember token & session (belum ada test untuk alur ini).

### B.16 Reflected XSS pada Input Pencarian (None — Informational)

- **ID:** F-17
- **Severity:** Informational
- **Status:** Confirmed (negatif/hardening)
- **Judul:** Input pencarian di-render via `{{ }}` (escape) — tidak ada stored/reflected XSS terkonfirmasi, tapi karakter `\` dan backslash di search belum dinormalisasi
- **File:** `app/Http/Controllers/Admin/EmployeeController.php:19-21`, `Admin/AttendanceController.php:77-88`, `resources/views/admin/employees/index.blade.php:12`
- **Bukti / alur eksploitasi:** Nilai `search` di-echo dengan `{{ $search }}` di Blade (escaped) sehingga XSS terhindar. Query `LIKE "%{$search}%"` memakai parameter binding. Input backslash/`%`/`_` bisa mengubah pola LIKE (wildcard injection) yang berdampak pada hasil pencarian, bukan pada eksekusi — dampak rendah.
- **Dampak:** Rendah; hasil pencarian bisa dimanipulasi wildcard, bukan injeksi.
- **Rekomendasi:** **P2** — escape `%`/`_`/`\` pada input LIKE, atau gunakan `Escaper`. Tidak ada perbaikan mendesak.
- **Test yang disarankan:** Test pencarian berisi `%`/`_` tidak mengembalikan outlier; test pencarian berisi `<script>` dirender sebagai teks (lifted positive).

---

## C. Temuan Positif (Perlindungan yang Sudah Benar)

| # | Area | Bukti |
|---|------|-------|
| P1 | **Rate limiting login** kuat (per-akun 5, per-IP 20, lock 300s) | `LoginController.php:34-49,76-77`; test `LoginSecurityTest` |
| P2 | **Session regenerate + login** dan **logout invalidate + regenerateToken** | `LoginController.php:58,91-94` |
| P3 | **CSRF aktif** untuk semua route web, `$except` kosong | `VerifyCsrfToken.php:14`; `web.php` (semua state-changing di group web) |
| P4 | **IDOR dicegah** pada attendance history & leave requests employee | `AttendanceHistoryController.php:34`, `Employee/LeaveRequestController.php:86-90`; test authorization |
| P5 | **Pemisahan role admin/employee** + pengecekan `status` di middleware | `AdminMiddleware.php`, `EmployeeMiddleware.php:17-28`; test `AdminCrudAuditTest` |
| P6 | **Proteksi input SQLi** — semua query parameterized; raw SQL parameterized (`whereRaw` dengan binding) | `ShiftAssignmentController.php:105`, `AttendanceController.php:22` (selectRaw statis) |
| P7 | **Output escaping** seluruh Blade `{{ }}` & DOM dibangun dengan `textContent` (bukan innerHTML) | seluruh views; `admin/dashboard.blade.php:200-243`; grep `{!!` → kosong |
| P8 | **Security headers** (CSP, nosniff, XFO, Referrer-Policy, Permissions-Policy, COOP, HSTS saat prod+HTTPS) | `SecurityHeaders.php:19-27`; test `SecurityHardeningTest` |
| P9 | **GPS challenge nonce** sekali pakai, terikat user_id + action, TTL 5 menit, `Cache::pull` anti-replay | `AttendanceController.php:18-31,173-188`; test `AttendanceGpsSecurityTest` |
| P10 | **Double-submission dicegah** — `lockForUpdate` + unique constraint `(employee_id, attendance_date)` + catch QueryException | `AttendanceController.php:67-91`; migrasi `attendances:60` |
| P11 | **Validasi radius & akurasi GPS** server-side | `AttendanceController.php:195-213`; test `AttendanceFlowTest` |
| P12 | **Formula/CSV injection di-escape** pada export (prefix `'`) | `AttendanceRecapSheet.php:48-53`; test `AttendanceRecapExportTest` |
| P13 | **Consistency izin/absensi** — cek approve-izin vs attendance & tidak- bisa-absen-saat-libur/izin | `Admin/LeaveRequestController.php:89-101`, `AttendanceController.php:42-48`; test `LeaveAttendanceIntegrationTest` |
| P14 | **Integritas FK & soft-delete** — FK `restrict`/`cascade` diatur, penghapusan berhistori → deaktivasi akun | migrasi; `EmployeeController.php:111-131`, `WorkScheduleController.php:75-93` |
| P15 | **Remember token dirotasi** saat ganti/reset password (admin & employee) | `EmployeeController.php:96`, `Admin/ProfileController.php:66`, `Employee/ProfileController.php:61`; test `SecurityHardeningTest` |
| P16 | **Akun nonaktif langsung logout** di semua request terautentikasi | middleware admin/employee |
| P17 | **.env tidak di-commit ke git** | `git ls-files` → hanya `.env.example` |
| P18 | **Attachment disimpan privat** (disk `local`) & hanya via endpoint terotorisasi | `LeaveRequestController.php:54,79-84` |
| P19 | **Password di-hash** (bcrypt via `Hash::make`) & field `password`/`remember_token` di-`hidden` | `User.php:32-35` |

---

## D. Prioritas Perbaikan

### P0 — Wajib sebelum deployment
1. **F-01** Upgrade Laravel (LTS) + PHP yang masih didukung; atau isolasi risiko file-upload/email (validasi magic-bytes, hindari rule `mimes` bawaan yang rentan).
2. **F-02** `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`.
3. **F-03** Export Excel: wajibkan rentang tanggal (maks. 31 hari) + throttle + batasi sheet / antri export.
4. **F-05** `SESSION_SECURE_COOKIE=true` + pastikan HTTPS-only di produksi.

### P1 — Sangat disarankan
5. **F-04** Index terpisah `attendance_date`/kolom filter + cache agregat dashboard; kurangi interval polling.
6. **F-06** Validasi magic-byte file (finfo) & jangan pakai ekstensi user untuk nama tersimpan.
7. **F-07** Aktifkan `TrustHosts`; set `TrustProxies::$proxies` ke IP load-balancer; verifikasi `X-Forwarded-Proto`.
8. **F-08** Nonaktifkan/rapikan guard `api` & `/api/user` & CORS bila tidak dipakai.
9. **F-11** Dokumentasikan & mitigasi spoofing GPS (flag verifikasi manual; kuatkan anomali).
10. **F-12** Aktifkan `AuthenticateSession`; invalidasi sesi aktif saat ganti password.
11. **F-13** Tambah index untuk query rentang + dashboard.

### P2 — Hardening lanjutan
12. **F-14** Level log produksi `warning`/`error` + audit redaction secret.
13. **F-15** Pindahkan script inline, hilangkan `'unsafe-inline'` dari `script-src`, self-host CDN.
14. **F-16** Kebijakan self-service reset / expiry password.
15. **F-17** Escape wildcard pada pencarian LIKE.

---

## E. Deployment Verdict

> ## **NOT READY**

**Alasan:** Meskipun fondasi keamanan aplikasi (auth, CSRF, otorisasi, GPS challenge, locking, escaping output, formula-escape) sudah kuat dan teruji, terdapat **blokir P0 yang belum terpenuhi** untuk pemasangan di server kantor:
1. Framework **Laravel 8 + PHP 7.4 sudah EOL** dengan **CVE-2025-27515 (file validation bypass)** yang **relevan langsung dengan fitur upload lampiran** dan tidak akan di-patch — ini adalah risiko nyata yang harus diselesaikan atau dimitigasi secara khusus sebelum go-live.
2. Konfigurasi produksi esensial belum disiapkan (`APP_DEBUG`, cookie `Secure`, trusted host/proxy).
3. Endpoint ekspor Excel memiliki jalur eksfiltrasi seluruh data & potensi DoS tanpa rentang tanggal wajib/throttle.

Setelah item P0 (dan idealnya P1 kunci) dibereskan, status dapat dinaikkan menjadi **READY WITH CONDITIONS**. Aplikasi tidak boleh dinyatakan "aman" semata karena test keamanan yang ada lulus — test tersebut valid dan berharga, tetapi tidak menutupi risiko framework EOL dan konfigurasi produksi yang belum diterapkan.

---

## F. Pengujian Keamanan — Tinjauan & Usulan

### F.1 Test yang Sudah Ada (baik)
- `LoginSecurityTest` — rate limit akun & IP, remember cookie, redirect role.
- `AttendanceGpsSecurityTest` — nonce anti-replay, stale GPS ditolak, valid GPS disimpan.
- `AttendanceFlowTest` — radius & akurasi ditolak, double check-in, check-out tanpa check-in, night-shift lintas tanggal.
- `AdminCrudAuditTest` — session inactive dihentikan, schedule invalid, shift change after attendance, correction clears reasons.
- `LeaveRequestAuthorizationTest` + `AttendanceHistoryAuthorizationTest` — IDOR 403.
- `AdminDashboardLiveTest` — admin dapat, employee 403.
- `SecurityHardeningTest` — security headers, rotate remember token, password beda, reset revoke token.
- `AttendanceRecapExportTest` — filter konsisten, date order, formula escape.
- `LeaveAttendanceIntegrationTest` — approve-leave prevents check-in.
- `Browser/AuthenticationTest` (Dusk) — login/navigate/logout end-to-end.

### F.2 Skenario Keamanan Penting yang Belum Punya Test (prioritas)

| # | Skenario | Prioritas |
|---|----------|-----------|
| T1 | **File-upload bypass** — upload file berbahaya (HTML/PHP penyamar `.jpg`), polyglot, ekstensi ganda `x.jpg.php`; cek response & isi storage (terutama mengingat CVE-2025-27515) | **P0** |
| T2 | **Export tanpa/tanpa batas tanggal & throttle** — GET `/admin/attendances/export` tanpa `date_from/date_to` harus ditolak; rentang > maksimum ditolak; throttle berlaku | **P0** |
| T3 | **Konfigurasi produksi** — `APP_DEBUG=false` tak menampilkan stack trace; `SESSION_SECURE_COOKIE` true; `APP_ENV=production` | P1 |
| T4 | **SQLi attempt** — kirim `' OR '1'='1`, `UNION`, wildcard `%`/`_` pada `search`, `employee_id` non-numerik, tanggal invalid (pastikan error 4xx bukan query error/500 & tidak ada leak) | P1 |
| T5 | **Stored/reflected XSS** — masukkan `<script>`/`<img onerror>` di name/reason/review_note/jabatan, pastikan ter-escape di view (tanpa `{!!}`) | P1 |
| T6 | **Race condition konkuren** — parallel 2× POST check-in & check-out (proper http test / simulasi) → hanya 1 berhasil | P1 |
| T7 | **CSRF failure** — POST tanpa token CSRF → 419 (pastikan semua route state-changing menolak) | P1 |
| T8 | **Otorisasi export & attachment** — employee tidak bisa akses export; employee hanya unduh attachment sendiri (owner check) | P1 |
| T9 | **Challenge cross-action & expired** — nonce `check_in` tak bisa dipakai untuk `check_out`; nonce expired (>5 menit) ditolak | P1 |
| T10 | **Session invalidation lintas-perangkat** saat ganti password (setelah aktifkan `AuthenticateSession`) | P1 |
| T11 | **Host-header injection** dan **request()->ip() di belakang proxy** (TrustProxies/TrustHosts) | P2 |
| T12 | **Parameter tampering GPS** — `accuracy` negatif/0/NaN/`1e999`, `latitude` di luar range, `captured_at` future/past ekstrem; pastikan validasi `between`/`min` menolak dan tidak ada integer-overflow | P2 |
| T13 | **Dashboard live rate limit** — >12 request/menit → 429 | P2 |
| T14 | **Info disclosure** — halaman 404/403/419/500 di `APP_DEBUG=false` tidak memuat path/versi | P2 |

F.3 Catatan Metodologi: `phpunit.xml` memakai `DatabaseTransactions` pada mayoritas feature test tanpa menimpa DB produksi (dipakai DB test/dev). Pastikan pipeline CI menggunakan DB terpisah (contoh `sqlite :memory:` masih dikomentari di `phpunit.xml:24-25`) supaya tidak menyentuh DB pengembangan saat menjalankan test.

---

## Ringkasan Jumlah Temuan per Severity

| Severity | Status | Jumlah |
|----------|--------|--------|
| **Critical** | Confirmed | 1 |
| **High** | Confirmed / Potential | 4 |
| **Medium** | Confirmed / Potential / Hardening | 4 |
| **Low** | Confirmed / Hardening / Informational | 5 |
| **Informational** | — | 2 |
| **Total** | | **16** |

*(Rincian: Critical 1 → F-01. High 4 → F-02, F-03, F-05, F-11. Medium 4 → F-04, F-06, F-07, F-12. Low 5 → F-08, F-13, F-14, F-15, F-16. Informational 2 → F-17 + catatan P17/P18 positif.)*

---

*Laporan ini hanya audit read-only. Belum ada implementasi perbaikan yang dilakukan. Silakan tinjau & setujui sebelum langkah remediasi dimulai.*
