---
name: Indigo Attendance System
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#45474c'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#75777d'
  outline-variant: '#c5c6cd'
  surface-tint: '#545f73'
  primary: '#091426'
  on-primary: '#ffffff'
  primary-container: '#1e293b'
  on-primary-container: '#8590a6'
  inverse-primary: '#bcc7de'
  secondary: '#4648d4'
  on-secondary: '#ffffff'
  secondary-container: '#6063ee'
  on-secondary-container: '#fffbff'
  tertiary: '#111516'
  on-tertiary: '#ffffff'
  tertiary-container: '#26292b'
  on-tertiary-container: '#8d9092'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d8e3fb'
  primary-fixed-dim: '#bcc7de'
  on-primary-fixed: '#111c2d'
  on-primary-fixed-variant: '#3c475a'
  secondary-fixed: '#e1e0ff'
  secondary-fixed-dim: '#c0c1ff'
  on-secondary-fixed: '#07006c'
  on-secondary-fixed-variant: '#2f2ebe'
  tertiary-fixed: '#e0e3e5'
  tertiary-fixed-dim: '#c4c7c9'
  on-tertiary-fixed: '#191c1e'
  on-tertiary-fixed-variant: '#444749'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display-lg:
    fontFamily: Hanken Grotesk
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  display-lg-mobile:
    fontFamily: Hanken Grotesk
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Hanken Grotesk
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-caps:
    fontFamily: JetBrains Mono
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  stat-number:
    fontFamily: Hanken Grotesk
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-padding: 2rem
  gutter-base: 1.5rem
  stack-gap: 1rem
  sidebar-width: 260px
  section-margin: 2.5rem
---

## Brand & Style

Sistem desain ini dirancang untuk menciptakan lingkungan kerja digital yang profesional, efisien, dan terpercaya. Fokus utamanya adalah fungsionalitas SaaS modern yang memprioritaskan kejelasan informasi di atas dekorasi yang berlebihan.

**Kepribadian Brand:**
*   **Profesional & Terpercaya:** Menggunakan palet indigo yang dalam untuk memberikan kesan stabilitas korporat.
*   **Efisien:** Layout yang bersih dengan fokus pada data kehadiran dan aksi cepat.
*   **Modern:** Mengadopsi estetika *Modern Corporate* dengan sentuhan minimalis dan penggunaan ruang putih yang strategis.

**Gaya Visual:**
Gaya utama adalah **Corporate Modern**. Pendekatan ini menggabungkan struktur grid yang ketat dengan elemen visual yang lembut (seperti sudut membulat) untuk memastikan antarmuka terasa canggih namun tetap manusiawi dan mudah diakses oleh karyawan.

## Colors

Palet warna didominasi oleh perpaduan Indigo dan White yang bersih, memberikan kontras tinggi untuk keterbacaan maksimal.

*   **Primary (Indigo Night):** Digunakan untuk sidebar, navigasi utama, dan teks judul untuk memberikan kesan otoritas.
*   **Secondary (Electric Indigo):** Digunakan sebagai aksen untuk elemen interaktif seperti tombol utama, indikator aktif, dan ikon.
*   **Neutral & Background:** Menggunakan skala abu-abu kebiruan (slate) untuk menjaga konsistensi nada dingin yang profesional.
*   **Status Indicators:** Warna hijau (success), merah (danger), dan amber (warning) digunakan secara spesifik untuk indikator kehadiran, keterlambatan, dan permohonan izin.

## Typography

Sistem tipografi menggunakan kombinasi sans-serif modern untuk memastikan keterbacaan data yang cepat.

*   **Hanken Grotesk** digunakan untuk judul dan angka statistik guna memberikan karakter modern dan tajam.
*   **Inter** digunakan untuk teks isi (body) karena netralitas dan keterbacaannya yang luar biasa pada layar digital.
*   **JetBrains Mono** digunakan secara selektif untuk label data teknis (seperti waktu/jam masuk) untuk memberikan kesan presisi.

Hirarki informasi harus dijaga ketat: Nama karyawan dan status kehadiran utama harus selalu menonjol dibandingkan teks deskriptif.

## Layout & Spacing

Sistem ini menggunakan **Fluid Grid** dengan batasan kontainer maksimal pada layar desktop untuk menjaga fokus pengguna.

*   **Sidebar:** Tetap (fixed) di sisi kiri dengan lebar 260px untuk navigasi cepat.
*   **Card Layout:** Menggunakan susunan grid yang fleksibel. Pada desktop, dashboard menampilkan 4 kolom kartu aksi cepat, yang reflow menjadi 2 kolom pada tablet, dan 1 kolom pada mobile.
*   **Rhythm:** Menggunakan sistem spacing berbasis 8px (4, 8, 16, 24, 32, 48, 64) untuk menjaga konsistensi vertikal dan horizontal.
*   **Safe Areas:** Margin luar dashboard adalah 32px (2rem) untuk memberikan ruang napas pada konten agar tidak terasa sesak.

## Elevation & Depth

Sistem desain ini menggunakan kedalaman minimalis untuk menjaga performa visual tetap ringan.

*   **Tonal Layering:** Latar belakang utama menggunakan abu-abu sangat muda (#F8FAFC), sedangkan kartu (cards) menggunakan warna putih murni (#FFFFFF) untuk membedakan lapisan konten.
*   **Ambient Shadows:** Shadow hanya digunakan pada elemen yang dapat diklik (seperti kartu aksi) dengan blur radius yang lebar dan opasitas rendah (4-8%) menggunakan warna dasar indigo.
*   **Active State:** Elemen yang aktif atau sedang difokuskan menggunakan *low-contrast outline* berwarna secondary indigo untuk indikasi posisi tanpa mengganggu tata letak.

## Shapes

Bentuk dalam sistem desain ini mengikuti prinsip "Friendly Professionalism".

*   **Cards & Containers:** Menggunakan radius `rounded-lg` (16px/1rem) untuk memberikan tampilan modern dan menghilangkan kesan kaku pada sistem administrasi.
*   **Buttons & Inputs:** Menggunakan radius standard `rounded` (8px/0.5rem) untuk fungsionalitas yang presisi.
*   **Status Tags:** Menggunakan radius penuh (pill-shaped) untuk membedakan label status dari elemen interaktif lainnya.

## Components

**1. Aksi Cepat (Quick Action Cards):**
Kartu putih dengan ikon berwarna di pojok kiri atas. Judul tebal (Hanken Grotesk) diikuti sub-teks abu-abu. State hover harus memberikan elevasi sedikit lebih tinggi dan perubahan warna ikon yang lebih cerah.

**2. Sidebar Navigation:**
Latar belakang indigo gelap. Menu aktif harus memiliki indikator visual berupa background yang sedikit lebih terang atau border kiri berwarna Electric Indigo. Teks menu menggunakan Inter Medium 14px.

**3. Status Indicators (Badges):**
Gunakan latar belakang berwarna sangat muda (tint) dengan teks warna pekat. Contoh: "Hadir" menggunakan latar hijau muda dengan teks hijau tua.

**4. Input Fields:**
Gunakan border 1px solid slate-200. Saat fokus, border berubah menjadi Electric Indigo dengan ring shadow halus di sekelilingnya.

**5. Riwayat List:**
Daftar dalam format tabel bersih tanpa border vertikal, hanya border horizontal halus. Pastikan baris memiliki padding vertikal yang cukup (16px) untuk kemudahan pemindaian mata.

**6. Tombol (Buttons):**
*   **Primary:** Background Indigo, teks Putih.
*   **Secondary:** Outline Indigo, teks Indigo.
*   **Danger:** Background Merah, teks Putih (khusus untuk 'Keluar' atau pembatalan).