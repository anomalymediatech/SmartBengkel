# Implementation Plan: Dashboard + Grafik (Modul 6)

## Overview

Implementasi meningkatkan halaman Beranda Admin (`index.php`) dan Beranda Kasir (`kasir/index.php`) dari 2 widget sederhana menjadi dashboard informatif lengkap. Mencakup: widget statistik harian, grafik Chart.js via AJAX, alert jatuh tempo, widget stok menipis, widget laba kotor, dan endpoint JSON terpisah. Seluruh implementasi menggunakan PHP prosedural murni, `mysqli`, Bootstrap 3 (SB Admin 2), dan jQuery — tanpa framework.

---

## Tasks

- [x] 1. Buat endpoint AJAX `dashboard_data.php` untuk Dashboard Admin
  - [x] 1.1 Buat file `dashboard_data.php` di root dengan auth guard dan query omset per hari
    - Tambahkan `include("sess_check.php")` sebagai baris pertama
    - Jalankan query `SELECT DAY(tgl_trx), SUM(total) FROM trx WHERE MONTH/YEAR = bulan berjalan AND status_bayar != 'Batal' GROUP BY DAY(tgl_trx)`
    - Bangun array `$lookup[hari] = omset` dari result set
    - Loop `$d = 1..$days_in_month`: isi `$labels[]` dan `$data[]` (default `0.0` jika tidak ada di lookup)
    - Set header `Content-Type: application/json` lalu `echo json_encode(["labels" => $labels, "data" => $data])`
    - Pastikan semua nilai numerik dikembalikan sebagai `int`/`float`, bukan string
    - _Requirements: 3.2, 3.3, 3.4, 8.4, 8.5_

  - [ ]* 1.2 Tulis property test P3 — struktur JSON kontinu dan lengkap
    - Buat `tests/prop_dashboard_data_json.php`
    - **Property 3: JSON dashboard_data memiliki struktur kontinu dan lengkap**
    - Generator: random subset hari dalam bulan berjalan (0–N hari acak dengan omset acak)
    - Assertion: `count($labels) == days_in_month`, `count($data) == count($labels)`, semua gap berisi `0.0`, semua elemen `data` bertipe `int`/`float`
    - Jalankan minimum 100 iterasi
    - _Requirements: 3.3, 3.4, 8.5_

- [x] 2. Buat endpoint AJAX `kasir/dashboard_data.php` untuk Dashboard Kasir
  - [x] 2.1 Buat file `kasir/dashboard_data.php` dengan auth guard, filter `id_kasir`, dan query jumlah transaksi per hari
    - Tambahkan `include("sess_check.php")` sebagai baris pertama
    - Ambil `$id_kasir = (int)$_SESSION['kasir_id']`
    - Query `SELECT DAY(tgl_trx), COUNT(id_trx) FROM trx WHERE MONTH/YEAR = bulan berjalan AND id_kasir = $id_kasir AND status_bayar != 'Batal' GROUP BY DAY(tgl_trx)`
    - Bangun array `$lookup`, loop `$d = 1..$days_in_month`, isi gap dengan `0`
    - `echo json_encode(["labels" => $labels, "data" => $data])`
    - _Requirements: 4.1, 4.2, 4.3, 8.4, 8.5_

  - [ ]* 2.2 Tulis property test P4 — isolasi data kasir pada grafik batang
    - Buat `tests/prop_kasir_chart_isolation.php`
    - **Property 4: Isolasi data kasir pada grafik batang**
    - Generator: beberapa `id_kasir` berbeda, data `trx` acak dengan berbagai `id_kasir`
    - Assertion: nilai `data[i]` hanya menghitung transaksi dengan `id_kasir` yang cocok, bukan kasir lain
    - Jalankan minimum 100 iterasi
    - _Requirements: 4.2_

- [x] 3. Checkpoint — Verifikasi kedua endpoint AJAX
  - Jalankan kedua file endpoint via CLI (`php dashboard_data.php`, `php kasir/dashboard_data.php`) dan pastikan output JSON valid
  - Pastikan semua tes di `tests/prop_dashboard_data_json.php` dan `tests/prop_kasir_chart_isolation.php` lulus

- [x] 4. Bangun ulang `index.php` — Dashboard Admin
  - [x] 4.1 Tulis query statistik harian Admin dan struktur HTML dasar
    - Pertahankan `include("sess_check.php")` dan `include("dist/function/format_rupiah.php")` di baris pertama
    - Tulis 4 query sinkron: `$sql_stats` (omset + jml_trx dengan `COALESCE`), `$sql_items` (item terjual), `$sql_pelanggan` (pelanggan unik, kecualikan `id_kon = 0`), dan `$sql_laba` (laba kotor dengan `COALESCE`)
    - Gunakan `@$res = mysqli_query(...)` untuk query laba kotor (fallback `harga_modal_snap`) dan query stok menipis (fallback `stok_min`)
    - Set `$pagedesc = "Beranda"` dan `include("layout_top.php")`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 7.1, 8.1_

  - [ ]* 4.2 Tulis property test P7 — kalkulasi laba kotor akurat
    - Buat `tests/prop_laba_kotor.php`
    - **Property 7: Kalkulasi laba kotor akurat secara aritmetika**
    - Generator: kumpulan record acak dengan `subtotal`, `harga_modal_snap`, `jml` bervariasi
    - Assertion: hasil == `SUM(subtotal) - SUM(harga_modal_snap * jml)` dengan toleransi ±0.01
    - Jalankan minimum 100 iterasi
    - _Requirements: 7.1_

  - [x] 4.3 Render 4 Widget_Statistik baris atas + widget Laba Kotor
    - Render 4 panel (`col-lg-3 col-md-6`): "Omset Hari Ini" (panel-primary), "Jumlah Transaksi" (panel-yellow), "Item Terjual" (panel-green), "Pelanggan" (panel-teal)
    - Render widget Laba Kotor terpisah: kelas panel `panel-green` jika `$laba_kotor > 0`, `panel-red` jika `<= 0`
    - Semua nilai menampilkan `0` (atau `Rp 0`) jika query menghasilkan NULL
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 7.2, 7.3, 7.4_

  - [ ]* 4.4 Tulis property test P8 — warna panel laba kotor mencerminkan tanda nilai
    - Buat `tests/prop_laba_warna.php`
    - **Property 8: Warna panel laba kotor mencerminkan tanda nilai**
    - Generator: random float (positif, nol, negatif)
    - Assertion: nilai > 0 → kelas `panel-green`, nilai ≤ 0 → kelas `panel-red`; tidak ada kelas lain yang valid
    - Jalankan minimum 100 iterasi
    - _Requirements: 7.2, 7.3_

  - [x] 4.5 Render `include("alert_jatuh_tempo.php")` secara kondisional dan panel Stok Menipis
    - Tambahkan `include("alert_jatuh_tempo.php")` di atas widget (file ini sudah ada, tidak dimodifikasi)
    - Render panel "Stok Menipis" menggunakan `$sql_stok` (maks 5 item, `ORDER BY CAST(stok AS UNSIGNED) ASC LIMIT 5`)
    - Jika `@$res_stok` gagal (kolom `stok_min` tidak ada), sembunyikan panel seluruhnya — tanpa error fatal
    - Jika tidak ada item stok menipis, tampilkan teks "Tidak ada barang dengan stok menipis"
    - Tambahkan tautan "Lihat Semua" ke halaman manajemen barang
    - _Requirements: 5.1, 5.2, 5.3, 6.1, 6.2, 6.3, 6.4_

  - [ ]* 4.6 Tulis property test P5 — visibilitas alert jatuh tempo berkorelasi dengan data
    - Buat `tests/prop_alert_visibility.php`
    - **Property 5: Visibilitas alert jatuh tempo berkorelasi tepat dengan data**
    - Generator: kumpulan record piutang/hutang dengan `jatuh_tempo` dan `status_bayar` acak
    - Assertion: alert tampil ↔ ada minimal 1 record dengan `jatuh_tempo <= CURDATE() + INTERVAL 3 DAY` dan `status_bayar = 'Belum Lunas'`
    - Jalankan minimum 100 iterasi
    - _Requirements: 5.1, 5.2_

  - [ ]* 4.7 Tulis property test P6 — panel stok menipis hanya tampilkan barang yang memenuhi kondisi
    - Buat `tests/prop_stok_menipis.php`
    - **Property 6: Panel stok menipis hanya menampilkan barang yang memenuhi kondisi dan dibatasi 5**
    - Generator: kumpulan record `barangjasa` acak dengan berbagai nilai `stok` dan `stok_min`
    - Assertion: setiap item yang ditampilkan memenuhi `CAST(stok AS UNSIGNED) <= stok_min`; jumlah item ≤ 5
    - Jalankan minimum 100 iterasi
    - _Requirements: 6.1, 6.3_

  - [x] 4.8 Render `<canvas>` grafik dan inisialisasi Chart.js via AJAX
    - Tambahkan tag `<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>` di area `<head>` melalui modifikasi layout atau inline sebelum `layout_bottom.php`
    - Render `<canvas id="salesChart">` dalam panel grafik
    - Tulis blok `<script>`: `$.getJSON("dashboard_data.php").done(callback).fail(errorHandler)`
    - Di callback: inisialisasi `new Chart(ctx, {type: 'line', ...})` dengan data dari JSON
    - Di `.fail()`: tampilkan `<p class="text-danger">Data grafik tidak dapat dimuat.</p>` di `#chart-container`
    - Tutup dengan `include("layout_bottom.php")`
    - _Requirements: 3.1, 3.2, 3.5, 3.6_

- [x] 5. Checkpoint — Verifikasi Dashboard Admin
  - Pastikan semua property test untuk komponen Admin lulus
  - Periksa secara manual: 4 widget tampil, widget laba kotor berwarna sesuai, panel stok menipis, grafik line Chart.js ter-render

- [x] 6. Bangun ulang `kasir/index.php` — Dashboard Kasir
  - [x] 6.1 Tulis query statistik harian Kasir dan struktur HTML dasar
    - Pertahankan `include("sess_check.php")` (versi kasir) sebagai baris pertama
    - Ambil `$id_kasir = (int)$_SESSION['kasir_id']`
    - Tulis 2 query sinkron: `$sql_kasir` (jml_trx + omset dengan filter `id_kasir` dan `COALESCE`) dan `$sql_items_kasir` (item terjual kasir)
    - Set `$pagedesc = "Beranda"` dan `include("layout_top.php")`
    - **Tidak** menyertakan `include("alert_jatuh_tempo.php")`, query laba kotor, atau query stok menipis
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 5.5, 8.2_

  - [ ]* 6.2 Tulis property test P2 — isolasi data kasir pada widget statistik
    - Buat `tests/prop_kasir_isolation.php`
    - **Property 2: Isolasi data kasir pada widget statistik**
    - Generator: random `id_kasir`, data `trx` campuran dari beberapa kasir berbeda
    - Assertion: nilai jml_trx, item_terjual, omset hanya berasal dari transaksi `id_kasir` yang cocok
    - Jalankan minimum 100 iterasi
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 6.3 Render 3 Widget_Statistik Kasir
    - Render 3 panel (`col-lg-4 col-md-6`): "Transaksi Hari Ini" (panel-primary), "Item Terjual" (panel-green), "Omset Saya Hari Ini" (panel-yellow)
    - Semua nilai default `0` / `Rp 0` jika tidak ada data
    - Pastikan **tidak ada** elemen HTML widget laba kotor, panel stok menipis, atau blok `alert-blink`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 6.4 Render `<canvas>` grafik batang dan inisialisasi Chart.js via AJAX
    - Render `<canvas id="kasirChart">` dalam panel grafik
    - Tulis blok `<script>`: `$.getJSON("kasir/dashboard_data.php").done(callback).fail(errorHandler)`
    - Di callback: inisialisasi `new Chart(ctx, {type: 'bar', ...})` dengan data dari JSON
    - Di `.fail()`: tampilkan pesan "Data grafik tidak dapat dimuat" di area grafik
    - Tutup dengan `include("layout_bottom.php")`
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 7. Tulis property test lintas fitur

  - [ ]* 7.1 Tulis property test P1 — `format_rupiah` selalu menghasilkan string bertipe Rupiah
    - Buat `tests/prop_format_rupiah.php`
    - **Property 1: format_rupiah selalu menghasilkan string bertipe Rupiah**
    - Generator: random int/float 0–999_999_999 (termasuk 0, nilai bulat, dan desimal)
    - Assertion: output diawali "Rp", hanya mengandung digit, titik, dan prefix "Rp"; tidak ada karakter lain
    - Jalankan minimum 100 iterasi
    - _Requirements: 1.1, 2.3_

  - [ ]* 7.2 Tulis property test P9 — auth gate mencegah akses tanpa sesi valid
    - Buat `tests/prop_auth_gate.php`
    - **Property 9: Authentication gate mencegah akses tanpa sesi valid**
    - Simulasikan request tanpa `$_SESSION` valid ke `index.php` dan `dashboard_data.php`
    - Assertion: output mengandung header `Location: login.php`, tidak ada data widget/JSON yang dikembalikan, eksekusi berhenti setelah redirect
    - Jalankan minimum 100 iterasi
    - _Requirements: 8.3, 8.4_

- [ ] 8. Final Checkpoint — Pastikan semua tes lulus
  - Jalankan seluruh skrip di folder `tests/`: `php tests/prop_format_rupiah.php`, `php tests/prop_kasir_isolation.php`, `php tests/prop_dashboard_data_json.php`, dst.
  - Pastikan semua tes lulus, tanyakan ke user jika ada pertanyaan sebelum lanjut.

---

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk rilis MVP lebih cepat
- Setiap task merujuk ke nomor requirement spesifik untuk traceability
- Property test diimplementasikan sebagai skrip PHP CLI dengan generator loop manual (minimum 100 iterasi) — tanpa Composer
- Gunakan `@$res = mysqli_query(...)` (operator `@`) sesuai konvensi codebase yang ada untuk kolom opsional (`harga_modal_snap`, `stok_min`)
- `$id_kasir` selalu di-cast ke `(int)` sebelum dimasukkan ke query untuk mencegah SQL injection
- Chart.js dimuat dari CDN (`https://cdn.jsdelivr.net/npm/chart.js`) — pastikan ada koneksi internet saat pengembangan
- Semua query aggregasi menggunakan `COALESCE(..., 0)` untuk menghindari nilai NULL dari `SUM()`/`COUNT()` pada result set kosong

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1"] },
    { "id": 1, "tasks": ["1.2", "2.2", "4.1", "6.1"] },
    { "id": 2, "tasks": ["4.2", "4.3", "6.2", "6.3"] },
    { "id": 3, "tasks": ["4.4", "4.5", "6.4"] },
    { "id": 4, "tasks": ["4.6", "4.7", "4.8"] },
    { "id": 5, "tasks": ["7.1", "7.2"] }
  ]
}
```
