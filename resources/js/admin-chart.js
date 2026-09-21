/**
 * Entry bundle Chart.js khusus dashboard admin untuk grafik batang, donut, dan garis.
 * Data awal disediakan oleh Blade/dashboard endpoint; file ini tidak memiliki akses langsung ke database.
 * Dimuat hanya pada dashboard admin agar halaman lain tidak menanggung ukuran library grafik.
 */
import Chart from 'chart.js/auto';

window.Chart = Chart;
