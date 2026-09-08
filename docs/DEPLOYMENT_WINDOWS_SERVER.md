# Deployment SiPrega pada Windows Server

Dokumen ini mempertahankan batasan server Balmon: PHP 7.4 dan Laravel 8. Jangan menyalin `.env` lokal atau `.env.dusk.local` ke produksi.

## 1. Informasi yang wajib dikonfirmasi

- Domain/IP resmi aplikasi.
- Web server: IIS atau Apache.
- Apakah TLS/HTTPS berhenti langsung di web server atau di reverse proxy.
- IP reverse proxy (jika ada).
- Lokasi instalasi PHP, Composer, MySQL, dan folder aplikasi.
- Akun Windows yang menjalankan application pool/service web.

## 2. Prasyarat

- PHP 7.4.33 sesuai kebijakan server, dengan ekstensi: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, dan `zip`.
- Composer 2 yang masih dapat berjalan pada PHP 7.4.
- MySQL dan akun database khusus aplikasi (bukan `root`).
- HTTPS dengan sertifikat resmi/internal perusahaan.
- IIS URL Rewrite bila menggunakan IIS, atau `mod_rewrite` bila menggunakan Apache.

Validasi ekstensi:

```powershell
php -m
php -r "echo class_exists('finfo') ? 'fileinfo OK' : 'fileinfo TIDAK ADA';"
```

## 3. Penempatan aplikasi

1. Salin source code ke folder versi/release baru, bukan menimpa release aktif.
2. Jalankan `composer install --no-dev --prefer-dist --optimize-autoloader`.
3. Salin `.env.production.example` menjadi `.env`, kemudian isi secret langsung di server.
4. Jalankan `php artisan key:generate` hanya untuk instalasi baru. Jangan mengganti `APP_KEY` pada aplikasi yang sudah memiliki data/session terenkripsi.
5. Arahkan document root web server ke folder `public`, bukan root project.

Contoh:

```text
D:\Aplikasi\SiPrega\current\public
```

Folder `.env`, `vendor`, `storage`, source PHP, dan backup tidak boleh menjadi document root.

## 4. Konfigurasi `.env` produksi

Nilai minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://host-resmi-balmon
APP_TIMEZONE=Asia/Jakarta
LOG_CHANNEL=daily
LOG_LEVEL=warning
TRUSTED_HOSTS=host-resmi-balmon
TRUSTED_PROXIES=
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Jika ada reverse proxy, isi `TRUSTED_PROXIES` hanya dengan IP proxy yang benar. Jangan menggunakan `*`.

## 5. Permission Windows

Akun web server hanya memerlukan hak baca/eksekusi terhadap aplikasi. Berikan hak Modify hanya pada:

```text
storage
bootstrap\cache
```

Jangan memberikan `Everyone: Full Control`. Konfigurasi ACL harus dilakukan administrator server sesuai akun IIS Application Pool atau service Apache yang digunakan.

## 6. Database dan migration

1. Backup database sebelum migration.
2. Uji migration pada salinan database.
3. Aktifkan maintenance mode.
4. Jalankan migration produksi sekali.

```powershell
php artisan down --retry=60
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Migration Dusk dan perintah `migrate:fresh` tidak boleh dijalankan pada database produksi.

## 7. Verifikasi setelah deployment

- Login admin dan pegawai berhasil.
- Akun nonaktif ditolak.
- Route admin ditolak untuk pegawai.
- Absen masuk/pulang meminta GPS dan bekerja melalui HTTPS.
- Shift siang, malam, dan libur sesuai.
- Izin/sakit dapat diajukan dan diproses.
- Lampiran palsu ditolak dan lampiran valid dapat diunduh sebagai attachment.
- Ekspor wajib memiliki periode maksimal 31 hari.
- Dashboard polling bekerja tanpa error browser.
- Halaman error tidak menampilkan stack trace/path server.
- Response HTTPS mengandung HSTS dan cookie session memiliki flag Secure/HttpOnly.

## 8. Backup

Backup harian minimal mencakup:

- Database MySQL.
- `storage/app/leave-attachments`.
- `.env` secara terenkripsi dan dengan akses terbatas.

Simpan backup di lokasi terpisah dari server aplikasi, tetapkan retensi, dan lakukan uji restore berkala. Backup dianggap valid hanya setelah restore berhasil diuji.

## 9. Rollback

Jika deployment gagal:

1. Aktifkan maintenance mode.
2. Simpan log error dan catat migration terakhir.
3. Kembalikan pointer/document root ke release sebelumnya.
4. Pulihkan database dari backup jika migration mengubah skema secara tidak kompatibel.
5. Bersihkan dan bangun ulang cache konfigurasi pada release lama.
6. Nonaktifkan maintenance mode dan jalankan smoke test.

Jangan melakukan `git reset --hard`, menghapus folder release aktif, atau rollback database tanpa backup yang telah diverifikasi.
