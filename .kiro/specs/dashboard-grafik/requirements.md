# Requirements Document

## Introduction

Fitur ini meningkatkan halaman Beranda (dashboard) aplikasi SmartBengkel dari tampilan 2 widget sederhana (jumlah transaksi & pendapatan hari ini) menjadi dashboard informasi lengkap. Peningkatan mencakup: widget statistik harian yang lebih kaya, grafik penjualan bulanan berbasis Chart.js, dan integrasi alert jatuh tempo piutang/hutang. Dashboard tersedia dalam dua varian: **Admin** (`root/index.php`) dan **Kasir** (`kasir/index.php`), dengan konten yang sedikit berbeda sesuai wewenang masing-masing role.

Seluruh implementasi mengikuti stack yang ada: PHP prosedural murni, `mysqli`, Bootstrap 3 (SB Admin 2), jQuery, tanpa framework, tanpa Composer. Tidak ada perubahan skema database — semua data diambil dari tabel `trx`, `trx_detail`, `barangjasa`, dan `konsumen` yang sudah ada.

---

## Glossary

- **Dashboard_Admin**: Halaman `index.php` di direktori root, hanya dapat diakses oleh role Admin.
- **Dashboard_Kasir**: Halaman `index.php` di direktori `kasir/`, hanya dapat diakses oleh role Kasir.
- **Widget_Statistik**: Kotak ringkasan satu metrik (panel SB Admin 2) yang ditampilkan di bagian atas dashboard.
- **Grafik_Penjualan**: Visualisasi data omset harian dalam satu bulan kalender menggunakan Chart.js.
- **Alert_Jatuh_Tempo**: Komponen peringatan berkedip yang sudah ada di `alert_jatuh_tempo.php`, menampilkan piutang pelanggan dan hutang supplier yang mendekati atau melewati jatuh tempo (≤ 3 hari).
- **Omset_Hari_Ini**: Total kolom `total` pada tabel `trx` untuk `tgl_trx = CURDATE()` dengan `status_bayar != 'Batal'`.
- **Item_Terjual**: Total `SUM(jml)` dari tabel `trx_detail` yang terhubung ke transaksi hari ini.
- **Pelanggan_Unik**: Jumlah `COUNT(DISTINCT id_kon)` pada tabel `trx` untuk transaksi hari ini dengan `id_kon != 0` (mengecualikan konsumen "Umum").
- **Laba_Kotor**: Selisih antara `SUM(subtotal)` dan `SUM(harga_modal_snap * jml)` dari `trx_detail` untuk periode tertentu.
- **Stok_Menipis**: Barang dengan `jenis = 'barang'` dan `CAST(stok AS UNSIGNED) <= stok_min`.
- **Chart_js**: Library grafik JavaScript yang dimuat dari CDN (`https://cdn.jsdelivr.net/npm/chart.js`).
- **Dashboard_Data_php**: File PHP terpisah (`dashboard_data.php`) yang menyajikan data grafik dalam format JSON untuk dikonsumsi oleh Chart.js via AJAX.

---

## Requirements

### Requirement 1: Widget Statistik Harian — Dashboard Admin

**User Story:** Sebagai Admin, saya ingin melihat 4 metrik kinerja hari ini di satu layar, sehingga saya dapat memantau kondisi bengkel secara cepat tanpa membuka halaman lain.

#### Acceptance Criteria

1. THE Dashboard_Admin SHALL menampilkan Widget_Statistik "Omset Hari Ini" yang memuat nilai `SUM(total)` dari tabel `trx` untuk `tgl_trx = CURDATE()` diformat sebagai Rupiah menggunakan fungsi `format_rupiah()` yang sudah ada.
2. THE Dashboard_Admin SHALL menampilkan Widget_Statistik "Jumlah Transaksi" yang memuat `COUNT(id_trx)` dari tabel `trx` untuk `tgl_trx = CURDATE()`.
3. THE Dashboard_Admin SHALL menampilkan Widget_Statistik "Item Terjual" yang memuat nilai Item_Terjual hari ini dari `trx_detail`.
4. THE Dashboard_Admin SHALL menampilkan Widget_Statistik "Pelanggan" yang memuat nilai Pelanggan_Unik hari ini.
5. WHEN data transaksi hari ini tidak ada (tidak ada record `trx` untuk `tgl_trx = CURDATE()`), THE Dashboard_Admin SHALL menampilkan nilai `0` (atau `Rp 0`) pada setiap Widget_Statistik tanpa pesan error. WHEN data transaksi tersedia, THE Dashboard_Admin SHALL menampilkan nilai aktual yang dihitung dari query database.
6. THE Dashboard_Admin SHALL menampilkan keempat Widget_Statistik dalam satu baris grid Bootstrap (`col-lg-3 col-md-6`) sehingga responsif di layar desktop dan tablet.

---

### Requirement 2: Widget Statistik Harian — Dashboard Kasir

**User Story:** Sebagai Kasir, saya ingin melihat ringkasan transaksi hari ini di dashboard saya, sehingga saya dapat mengetahui performa kasir saya sendiri tanpa mengakses data finansial lengkap.

#### Acceptance Criteria

1. THE Dashboard_Kasir SHALL menampilkan Widget_Statistik "Transaksi Hari Ini" yang memuat `COUNT(id_trx)` dari tabel `trx` untuk `tgl_trx = CURDATE()` dan `id_kasir = [id kasir yang sedang login]`.
2. THE Dashboard_Kasir SHALL menampilkan Widget_Statistik "Item Terjual" yang memuat Item_Terjual untuk transaksi kasir yang sedang login hari ini.
3. THE Dashboard_Kasir SHALL menampilkan Widget_Statistik "Omset Saya Hari Ini" yang memuat `SUM(total)` untuk transaksi kasir yang sedang login hari ini, diformat sebagai Rupiah.
4. WHEN data transaksi kasir yang bersangkutan hari ini tidak ada, THE Dashboard_Kasir SHALL menampilkan nilai `0` pada setiap Widget_Statistik tanpa pesan error.
5. THE Dashboard_Kasir SHALL TIDAK menampilkan widget Laba_Kotor, data kasir lain, atau ringkasan keuangan bengkel secara keseluruhan.

---

### Requirement 3: Grafik Penjualan Bulanan — Dashboard Admin

**User Story:** Sebagai Admin, saya ingin melihat grafik tren omset harian selama bulan berjalan, sehingga saya dapat mengidentifikasi hari-hari dengan penjualan tinggi dan rendah.

#### Acceptance Criteria

1. THE Dashboard_Admin SHALL menampilkan Grafik_Penjualan berupa grafik garis (type `line`) menggunakan Chart_js yang merender data omset per hari untuk bulan dan tahun kalender berjalan.
2. WHEN halaman dashboard dimuat, THE Dashboard_Admin SHALL mengambil data grafik dari Dashboard_Data_php via AJAX (`$.getJSON`) dan merender Grafik_Penjualan tanpa reload halaman.
3. THE Dashboard_Data_php SHALL mengembalikan data JSON dengan format `{"labels": ["1","2",...,"31"], "data": [omset_hari_1, omset_hari_2, ..., omset_hari_N]}` berdasarkan query `SELECT DAY(tgl_trx), SUM(total) FROM trx WHERE MONTH(tgl_trx)=MONTH(CURDATE()) AND YEAR(tgl_trx)=YEAR(CURDATE()) GROUP BY DAY(tgl_trx)`.
4. WHEN hari dalam bulan tidak memiliki transaksi, THE Dashboard_Data_php SHALL mengisi nilai `0` pada posisi hari tersebut dalam array `data` sehingga grafik tetap kontinu.
5. THE Grafik_Penjualan SHALL menampilkan label sumbu-X berupa tanggal (1–31) dan sumbu-Y berupa nilai omset dalam Rupiah.
6. IF permintaan AJAX ke Dashboard_Data_php gagal, THEN THE Dashboard_Admin SHALL menampilkan pesan "Data grafik tidak dapat dimuat" di area grafik tanpa menyebabkan error JavaScript yang menghentikan halaman.

---

### Requirement 4: Grafik Penjualan Bulanan — Dashboard Kasir

**User Story:** Sebagai Kasir, saya ingin melihat grafik tren transaksi saya sendiri bulan ini, sehingga saya dapat memantau performa kerja saya secara visual.

#### Acceptance Criteria

1. THE Dashboard_Kasir SHALL menampilkan Grafik_Penjualan berupa grafik batang (type `bar`) menggunakan Chart_js yang merender jumlah transaksi per hari untuk kasir yang sedang login selama bulan berjalan.
2. WHEN halaman dashboard kasir dimuat, THE Dashboard_Kasir SHALL mengambil data grafik dari endpoint AJAX yang memfilter data berdasarkan `id_kasir` sesi yang aktif.
3. WHEN hari dalam bulan tidak memiliki transaksi dari kasir bersangkutan, THE Dashboard_Kasir SHALL mengisi nilai `0` pada posisi hari tersebut sehingga grafik tetap kontinu.
4. IF permintaan AJAX gagal, THEN THE Dashboard_Kasir SHALL menampilkan pesan "Data grafik tidak dapat dimuat" di area grafik.

---

### Requirement 5: Integrasi Alert Jatuh Tempo di Dashboard

**User Story:** Sebagai Admin, saya ingin melihat peringatan jatuh tempo piutang/hutang langsung di halaman dashboard, sehingga saya tidak melewatkan kewajiban pembayaran yang mendesak.

#### Acceptance Criteria

1. WHEN halaman Dashboard_Admin dimuat dan terdapat piutang pelanggan atau hutang supplier dengan `jatuh_tempo <= CURDATE() + INTERVAL 3 DAY`, THE Dashboard_Admin SHALL menampilkan Alert_Jatuh_Tempo di atas Widget_Statistik dengan memanggil `include("alert_jatuh_tempo.php")`.
2. WHEN tidak ada piutang atau hutang yang mendekati jatuh tempo, THE Dashboard_Admin SHALL TIDAK menampilkan blok Alert_Jatuh_Tempo (tidak ada elemen HTML kosong yang terrender).
3. THE Alert_Jatuh_Tempo SHALL menampilkan tautan langsung ke halaman `piutang_v2.php` untuk piutang pelanggan dan ke `hutang_supplier_v2.php` untuk hutang supplier.
4. THE Alert_Jatuh_Tempo SHALL menggunakan animasi CSS `blink` (opacity berkedip) dengan kelas `.alert-blink` yang sudah terdefinisi di `custom.css`.
5. THE Dashboard_Kasir SHALL TIDAK menampilkan Alert_Jatuh_Tempo karena manajemen piutang/hutang adalah tanggung jawab Admin.

---

### Requirement 6: Widget Stok Menipis — Dashboard Admin

**User Story:** Sebagai Admin, saya ingin melihat daftar barang yang stoknya hampir habis di dashboard, sehingga saya dapat segera melakukan pemesanan sebelum stok benar-benar habis.

#### Acceptance Criteria

1. THE Dashboard_Admin SHALL menampilkan panel "Stok Menipis" ketika terdapat minimal satu item barang dengan kondisi `CAST(stok AS UNSIGNED) <= stok_min`, dengan memuat daftar dari query `SELECT nama, stok, stok_min FROM barangjasa WHERE jenis='barang' AND CAST(stok AS UNSIGNED) <= stok_min ORDER BY stok ASC`.
2. WHEN tidak ada barang dengan kondisi Stok_Menipis, THE Dashboard_Admin SHALL menampilkan teks "Tidak ada barang dengan stok menipis" di dalam panel tersebut.
3. THE Dashboard_Admin SHALL menampilkan maksimal 5 barang teratas (ORDER BY stok ASC LIMIT 5) di panel Stok_Menipis dengan tautan "Lihat Semua" ke halaman manajemen barang.
4. IF kolom `stok_min` tidak tersedia pada tabel `barangjasa`, THEN THE Dashboard_Admin SHALL melewati render panel Stok_Menipis tanpa menyebabkan error fatal pada halaman.

---

### Requirement 7: Widget Laba Kotor Hari Ini — Dashboard Admin

**User Story:** Sebagai Admin, saya ingin melihat estimasi laba kotor hari ini di dashboard, sehingga saya dapat mengetahui selisih antara omset dan modal barang yang terjual.

#### Acceptance Criteria

1. THE Dashboard_Admin SHALL menampilkan Widget_Statistik "Laba Kotor Hari Ini" yang dihitung dengan formula: `SUM(td.subtotal) - SUM(td.harga_modal_snap * td.jml)` dari tabel `trx_detail td JOIN trx t ON td.id_trx = t.id_trx` untuk `t.tgl_trx = CURDATE()`.
2. WHEN nilai Laba_Kotor bernilai positif, THE Dashboard_Admin SHALL menampilkan nilai tersebut dalam format Rupiah dengan latar panel berwarna hijau (`panel-green`).
3. WHEN nilai Laba_Kotor bernilai nol atau negatif, THE Dashboard_Admin SHALL menampilkan nilai tersebut dengan latar panel berwarna merah (`panel-red`) sebagai indikasi peringatan.
4. IF kolom `harga_modal_snap` tidak tersedia pada tabel `trx_detail`, THEN THE Dashboard_Admin SHALL menampilkan nilai `Rp 0` pada widget Laba Kotor tanpa menyebabkan error fatal.

---

### Requirement 8: Keamanan dan Akses Kontrol Dashboard

**User Story:** Sebagai pengelola sistem, saya ingin setiap dashboard hanya dapat diakses oleh role yang berwenang, sehingga data keuangan dan operasional terlindungi dari akses tidak sah.

#### Acceptance Criteria

1. THE Dashboard_Admin SHALL memeriksa sesi Admin melalui `include("sess_check.php")` pada baris pertama file sebelum memproses query atau merender HTML apapun.
2. THE Dashboard_Kasir SHALL memeriksa sesi Kasir melalui `include("sess_check.php")` yang berlokasi di direktori `kasir/` sebelum memproses query atau merender HTML apapun.
3. IF sesi tidak valid atau sudah kedaluwarsa, THEN THE Dashboard_Admin SHALL mengalihkan pengguna ke halaman login menggunakan `header('Location: login.php')` dan menghentikan eksekusi dengan `exit`. WHEN sesi valid dan aktif, THE Dashboard_Admin SHALL melanjutkan eksekusi normal tanpa redirect.
4. THE Dashboard_Data_php SHALL memeriksa validitas sesi yang sama sebelum mengembalikan data JSON, sehingga data tidak dapat diakses langsung tanpa autentikasi.
5. THE Dashboard_Data_php SHALL mengembalikan semua nilai numerik sebagai tipe `int` atau `float` (bukan string) dalam respons JSON untuk mencegah kesalahan kalkulasi di sisi JavaScript.
