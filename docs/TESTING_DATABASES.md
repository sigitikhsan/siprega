# Database Pengujian SiPrega

Dokumen ini menjelaskan pemisahan database agar PHPUnit dan Laravel Dusk tidak pernah
mengubah data demo maupun production.

## Pembagian database

- `absensi_pegawai`: development/demo lokal.
- `absensi_pegawai_prod`: production; dilarang untuk pengujian.
- `absensi_pegawai_testing`: PHPUnit dan Feature test lokal.
- `absensi_pegawai_dusk`: browser test Laravel Dusk.

`phpunit.xml` selalu mengarahkan PHPUnit ke `absensi_pegawai_testing`. Bootstrap test juga
akan menghentikan proses jika PHPUnit tidak memakai nama berakhiran `_testing`, atau jika
Dusk tidak memakai nama berakhiran `_dusk`.

## Persiapan pertama PHPUnit

1. Buat database kosong bernama `absensi_pegawai_testing` melalui phpMyAdmin/MySQL.
2. Jalankan migration hanya ke database tersebut:

```powershell
$env:DB_DATABASE = "absensi_pegawai_testing"
php artisan migrate --force
Remove-Item Env:\DB_DATABASE -ErrorAction SilentlyContinue
```

3. Jalankan test:

```powershell
php artisan test
```

Jangan menjalankan `db:seed`, PHPUnit, atau Dusk pada `absensi_pegawai_prod`. Pastikan
override PowerShell sudah dihapus setelah migration agar perintah artisan berikutnya kembali
menggunakan database pada `.env` lokal.

## Laravel Dusk

Dusk tetap menggunakan `.env.dusk.local` dan `absensi_pegawai_dusk`. File environment
tersebut bersifat lokal dan diabaikan Git. Pastikan database Dusk sudah dimigrasikan sebelum
menjalankan browser test.
