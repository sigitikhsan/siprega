# Persiapan Deployment SiPrega — Windows Server + XAMPP/Apache

Dokumen ini mencakup hasil audit kesiapan dan template konfigurasi. Konfigurasi Apache belum diterapkan ke mesin mana pun.

Lokasi yang disepakati untuk instalasi saat ini:

```text
Project : D:\Laravel\absensi-pegawai
Web root: D:\Laravel\absensi-pegawai\public
XAMPP   : C:\xampp
```

Project tidak perlu dipindahkan ke `C:\xampp\htdocs`. Apache Windows dapat menggunakan `DocumentRoot` pada drive D selama akun service mempunyai izin baca yang sesuai.

## 1. Hasil audit kesiapan

### Sudah siap

- Target aplikasi tetap Laravel 8 dan PHP 7.4 sesuai batas server.
- `APP_ENV=production`, `APP_DEBUG=false`, dan timezone `Asia/Jakarta` tersedia pada `.env.production.example`.
- Template production menggunakan log harian, session terenkripsi, cookie HTTP-only, dan cookie secure.
- Trusted host dan trusted proxy dapat dibatasi melalui environment; wildcard proxy tidak digunakan.
- Lampiran izin/sakit disimpan pada disk `local` (`storage/app`), bukan pada folder publik.
- Build production tidak memuat bundle global `app.js` yang tidak digunakan.
- Pemeriksaan `composer check-platform-reqs --no-dev` lulus pada PHP 7.4.33 saat audit.
- Kebutuhan ekstensi utama yang terverifikasi: DOM, Fileinfo, GD, JSON, OpenSSL, SimpleXML, XML, XMLReader, XMLWriter, ZIP, dan Zlib.

### Keputusan yang wajib diperoleh dari pengelola server

1. Nama domain/FQDN resmi aplikasi.
2. Sertifikat TLS beserta private key dan full chain yang sah.
3. Konfirmasi bahwa lokasi final project tetap `D:\Laravel\absensi-pegawai` dan tentukan akun Windows yang menjalankan service Apache.
4. Nama database, akun database khusus aplikasi, dan kebijakan backup kantor.
5. Apakah ada reverse proxy/load balancer di depan Apache. Jika tidak ada, `TRUSTED_PROXIES` harus kosong.
6. SMTP resmi jika notifikasi email akan dipakai; jika belum digunakan, jangan mengisi kredensial fiktif.

### Temuan dan batasan penting

- HTTPS wajib untuk penggunaan Geolocation browser pada hostname/IP produksi. Jangan meluncurkan absensi GPS melalui HTTP.
- `DocumentRoot` wajib mengarah ke `<project>/public`, bukan ke root project. Ini melindungi `.env`, source code, `vendor`, dan `storage`.
- Akun Apache hanya memerlukan hak **Modify** pada `storage` dan `bootstrap/cache`. Folder source lainnya cukup **Read & Execute**.
- Database produksi harus dibuat kosong lalu dimigrasikan tanpa menjalankan seeder data testing.
- `SESSION_DRIVER=file` dan `CACHE_DRIVER=file` memadai untuk satu server. Jika kelak dipasang pada beberapa server, session/cache perlu dipindahkan ke penyimpanan bersama.
- `QUEUE_CONNECTION=sync` berarti pekerjaan berjalan dalam request yang sama; konfigurasi service queue belum diperlukan pada versi sekarang.
- Lampiran aplikasi maksimal 5 MB. Template Apache memberi batas request 6 MB; `upload_max_filesize` dan `post_max_size` PHP tetap perlu diselaraskan.

### Hasil pemeriksaan XAMPP lokal saat penyusunan

- Apache lokal terdeteksi pada versi **2.4.54 (Win64)**.
- `rewrite_module`, `ssl_module`, `headers_module`, dan `alias_module` sudah aktif.
- `expires_module` dan `deflate_module` belum aktif pada konfigurasi lokal saat ini. Template tetap aman karena kedua bagian dibungkus `IfModule`, tetapi cache expiry dan kompresi baru bekerja setelah modul tersebut diaktifkan.
- Hasil lokal ini hanya referensi; mesin Windows Server tujuan wajib diperiksa kembali karena konfigurasinya dapat berbeda.

## 2. Template Apache yang disiapkan

Gunakan file berikut sebagai acuan, bukan dengan menyalinnya tanpa penyesuaian:

`deployment/apache/siprega-vhost.conf.example`

Template tersebut menyediakan:

- redirect HTTP ke HTTPS;
- VirtualHost TLS;
- `DocumentRoot` langsung ke folder `public`;
- dukungan `.htaccess` Laravel melalui `AllowOverride All`;
- directory listing yang dimatikan;
- batas request untuk lampiran;
- cache aset statis satu tahun dengan cache busting Laravel Mix;
- kompresi respons teks;
- log akses dan error terpisah.

Path utama yang sudah digunakan template:

```apache
DocumentRoot "D:/Laravel/absensi-pegawai/public"
<Directory "D:/Laravel/absensi-pegawai/public">
```

Sertifikat dicontohkan menggunakan direktori bawaan XAMPP `C:/xampp/apache/conf/ssl.crt` dan `C:/xampp/apache/conf/ssl.key`. Nama file SiPrega belum ada sampai sertifikat resmi diberikan. Jangan memakai `server.crt`/`server.key` bawaan XAMPP untuk production karena identitas host-nya tidak sesuai. Jika administrator memilih lokasi lain, kedua directive sertifikat harus disesuaikan bersama-sama.

### Modul Apache yang harus diperiksa nanti

Pastikan modul berikut aktif pada `httpd.conf` XAMPP:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule ssl_module modules/mod_ssl.so
LoadModule headers_module modules/mod_headers.so
LoadModule expires_module modules/mod_expires.so
LoadModule deflate_module modules/mod_deflate.so
```

File VirtualHost umumnya dimuat dari `apache/conf/extra/httpd-vhosts.conf`. Pastikan `httpd.conf` memiliki baris aktif:

```apache
Include conf/extra/httpd-vhosts.conf
```

### Nilai PHP yang perlu diperiksa nanti

Pada `php.ini` milik XAMPP, nilai minimum yang disarankan:

```ini
expose_php = Off
display_errors = Off
log_errors = On
upload_max_filesize = 5M
post_max_size = 6M
max_execution_time = 60
memory_limit = 256M
date.timezone = Asia/Jakarta
session.cookie_httponly = 1
session.cookie_secure = 1
```

Nilai `session.cookie_secure=1` hanya digunakan setelah HTTPS aktif.

## 3. Template environment production

Salin `.env.production.example` menjadi `.env` hanya di server. Jangan pernah melakukan commit terhadap `.env` produksi.

Nilai berikut wajib diganti:

- `APP_URL`
- `APP_KEY` melalui `php artisan key:generate`
- `TRUSTED_HOSTS`
- seluruh `DB_*`
- konfigurasi mail jika digunakan
- `WHATSAPP_ADMIN_NUMBER` jika fitur tersebut digunakan

Jika HTTPS belum aktif pada tahap verifikasi internal, `SESSION_SECURE_COOKIE` harus sementara `false`. Sebelum peluncuran resmi, aktifkan HTTPS dan kembalikan menjadi `true`.

## 4. Urutan instalasi yang direncanakan untuk tahap berikutnya

Perintah ini **belum dijalankan** pada server:

```powershell
Set-Location D:\Laravel\absensi-pegawai
composer install --no-dev --optimize-autoloader --no-interaction
Copy-Item .env.production.example .env
# Isi dan verifikasi .env server sebelum menjalankan perintah berikutnya.
php artisan key:generate
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Catatan:

- Isi `.env` setelah menyalin dan sebelum migration.
- `key:generate` hanya untuk instalasi baru. Jangan jalankan ulang pada aplikasi production yang sudah digunakan.
- Jangan menjalankan `db:seed` pada database produksi.
- Jangan menjalankan PHPUnit atau Dusk menggunakan `.env` production.
- Panduan database pengujian lokal tersedia di `docs/TESTING_DATABASES.md`.
- Aset production sebaiknya dibangun sebelumnya dengan `npm run production`, sehingga Node.js tidak wajib terpasang di server.
- Admin pertama dibuat setelah migrasi dengan prosedur terkontrol; password awal harus diganti saat serah terima.
- Sebelum setiap pembaruan, backup database, `.env`, dan `storage/app/leave-attachments`.

### Pemisahan database

- `absensi_pegawai`: development/demo lokal; boleh berisi data presentasi.
- `absensi_pegawai_prod`: production; hanya berisi data operasional kantor.
- `absensi_pegawai_testing`: PHPUnit/Feature test lokal; tidak boleh digunakan aplikasi.
- `absensi_pegawai_dusk`: Laravel Dusk lokal; tidak boleh digunakan aplikasi.

Sebelum migration production, pastikan `APP_ENV=production` dan `DB_DATABASE=absensi_pegawai_prod`.
Jangan menyalin data dari database demo ke production.

## 5. Checklist persetujuan sebelum instalasi

- [ ] Domain/IP resmi sudah ditetapkan.
- [ ] Sertifikat HTTPS tersedia dan valid.
- [ ] Akun service Apache diketahui.
- [ ] Akun database khusus aplikasi tersedia.
- [ ] Backup database sudah diuji untuk restore.
- [ ] Folder lampiran masuk cakupan backup.
- [ ] Database production kosong dan tidak memuat user testing.
- [ ] Template VirtualHost sudah disesuaikan serta direview mentor/admin server.
- [ ] Jadwal maintenance dan rollback disetujui.
