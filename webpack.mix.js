const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Build aset aplikasi
 |--------------------------------------------------------------------------
 |
 | Mengompilasi bundle Chart.js admin, React ticker pegawai, dan CSS aplikasi.
 | Bundle dipisah agar setiap halaman hanya mengunduh fitur yang digunakan;
 | daftar version() menjadi sumber cache-busting untuk helper mix() di Blade.
 |
 */

mix.js('resources/js/admin-chart.js', 'public/js/admin-chart.js')
    .js('resources/js/employee-attendance.js', 'public/js/employee-attendance.js')
    .react('resources/js/employee-schedule.jsx', 'public/js/employee-schedule.js')
    .postCss('resources/css/employee-schedule.css', 'public/css/employee-schedule.css')
    .version([
        'public/css/bootstrap-app.min.css',
        'public/css/employee-schedule.css',
        'public/vendor/bootstrap/5.3.3/bootstrap.bundle.min.js',
        'public/js/admin-chart.js',
        'public/js/employee-attendance.js',
        'public/js/employee-schedule.js'
    ])
    .disableNotifications();
