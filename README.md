# SmartBengkel — Sistem Informasi Bengkel v2.0

Aplikasi manajemen bengkel berbasis web yang dibangun di atas PHP prosedural murni, MySQL, Bootstrap 3 (SB Admin 2), dan jQuery. Dirancang untuk dijalankan di server lokal (XAMPP/Laragon) tanpa framework dan tanpa Composer.

---

## Fitur Utama

### Dashboard Admin (`index.php`)
- **4 widget statistik harian** — Omset, Jumlah Transaksi, Item Terjual, Pelanggan Unik
- **Widget Laba Kotor** — hijau jika positif, merah jika nol/negatif
- **Panel Stok Menipis** — 5 barang dengan stok paling rendah (dengan fallback jika kolom `stok_min` belum ada)
- **Alert Jatuh Tempo** — berkedip merah jika ada piutang/hutang ≤ 3 hari jatuh tempo
- **Grafik penjualan bulan ini** — grafik garis Chart.js via AJAX (`dashboard_data.php`)

### Dashboard Kasir (`kasir/index.php`)
- **3 widget statistik** khusus kasir yang sedang login — Transaksi, Item Terjual, Omset
- **Grafik transaksi bulan ini** — grafik batang Chart.js via AJAX (`kasir/dashboard_data.php`)
- Tidak menampilkan data keuangan bengkel (laba, stok, alert) — sesuai wewenang kasir

### Modul Lainnya
| Modul | Deskripsi |
|---|---|
| Kasir & Transaksi (POS) | Nota multi-item, diskon per item, metode bayar Tunai/Transfer/Hutang |
| WhatsApp & Cetak | Struk thermal 58/80mm, kirim struk via `wa.me` |
| Data Master & Import | Import barang dari CSV, manajemen konsumen dengan plat kendaraan |
| Hutang / Piutang | Catat piutang pelanggan & hutang supplier, cicilan, alert jatuh tempo |
| SDM & Penggajian | CRUD pegawai, slip gaji otomatis, kirim slip via WhatsApp |
| Laporan | Laporan laba kotor, laporan stok menipis |

---

## Struktur Direktori

```
SmartBengkel/
├── index.php                   # Dashboard Admin
├── dashboard_data.php          # Endpoint AJAX JSON grafik admin
├── kasir/
│   ├── index.php               # Dashboard Kasir
│   └── dashboard_data.php      # Endpoint AJAX JSON grafik kasir
├── tests/
│   ├── prop_dashboard_data_json.php   # Property test P3: struktur JSON
│   └── prop_kasir_chart_isolation.php # Property test P4: isolasi data kasir
├── dist/
│   ├── css/                    # CSS custom (sb-admin-2, custom.css)
│   └── function/
│       └── format_rupiah.php   # Helper format mata uang Rupiah
├── alert_jatuh_tempo.php       # Komponen alert berkedip
├── layout_top.php              # Header & sidebar HTML
├── layout_bottom.php           # Footer & script JS
├── sess_check.php              # Auth guard (redirect ke login jika sesi tidak valid)
├── PANDUAN_PENGGUNA.md         # Panduan penggunaan tiap modul
├── ROADMAP_UPGRADE.md          # Dokumentasi teknis & rencana upgrade
└── .kiro/specs/dashboard-grafik/  # Spec requirements, design, tasks (Modul 6)
```

---

## Cara Instalasi

### Prasyarat
- PHP 7.4+ dengan ekstensi `mysqli`
- MySQL / MariaDB
- Web server (Apache via XAMPP/Laragon, atau Nginx)
- Koneksi internet saat pertama kali membuka dashboard (Chart.js dimuat dari CDN)

### Langkah Setup

1. **Clone repo ke folder `htdocs` atau `www`:**
   ```bash
   git clone https://github.com/anomalymediatech/SmartBengkel.git
   ```

2. **Buat database dan import skema:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE db_bengkel;"
   mysql -u root -p db_bengkel < temp_repo/database/db_bengkel.sql
   ```
   Jika sudah pernah install versi sebelumnya, jalankan juga skrip upgrade:
   ```bash
   mysql -u root -p db_bengkel < sql/upgrade_v2.sql
   ```

3. **Konfigurasi koneksi database:**
   Edit `dist/config/koneksi.php` dan `kasir/koneksi.php`:
   ```php
   $conn = mysqli_connect("localhost", "root", "", "db_bengkel");
   ```

4. **Akses aplikasi:**
   - Admin: `http://localhost/SmartBengkel/`
   - Kasir: `http://localhost/SmartBengkel/kasir/`

### Login Default

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin` |
| Kasir | `kasir` | `password` |

> **Penting:** Ganti password default sebelum digunakan di lingkungan produksi.

---

## Stack Teknologi

| Layer | Teknologi |
|---|---|
| Backend | PHP 7.4+ prosedural, `mysqli` |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 3, SB Admin 2, jQuery, Font Awesome |
| Grafik | Chart.js (via CDN) |
| Template | Layout berbasis `include` (`layout_top.php`, `layout_bottom.php`) |

---

## Arsitektur Dashboard

Dashboard menggunakan pola **Server-Side Render + AJAX Lazy Load**:

- Widget statistik dirender server-side (sinkron) agar tampil instan saat halaman dimuat
- Data grafik diambil via `$.getJSON()` ke endpoint terpisah (`dashboard_data.php`) agar grafik bisa direfresh tanpa reload halaman
- Auth guard (`sess_check.php`) dipasang di baris pertama setiap file, termasuk endpoint AJAX

```
Browser → GET index.php
           ├── sess_check.php (auth)
           ├── Query PHP: widget statistik (sync)
           ├── HTML: widget, canvas placeholder
           └── JS: $.getJSON("dashboard_data.php") → Chart.js render

Browser → GET dashboard_data.php (AJAX)
           ├── sess_check.php (auth)
           └── JSON: { labels: [...], data: [...] }
```

---

## Property Tests

Folder `tests/` berisi skrip PHP CLI untuk memvalidasi correctness properties secara otomatis — tanpa Composer, cukup jalankan dengan PHP.

```bash
php tests/prop_dashboard_data_json.php
php tests/prop_kasir_chart_isolation.php
```

| File | Property yang Diuji |
|---|---|
| `prop_dashboard_data_json.php` | JSON endpoint admin memiliki struktur kontinu & lengkap (P3) |
| `prop_kasir_chart_isolation.php` | Data grafik kasir hanya memuat transaksi kasir yang login (P4) |

Setiap test berjalan minimum 100 iterasi dengan data acak.

---

## Catatan Keamanan

Beberapa masalah keamanan dari codebase asal yang perlu diperhatikan sebelum go-live:

- **SQL Injection** — query lama menggunakan string concatenation. File `_v2` sudah memitigasi dengan `intval()` dan `mysqli_real_escape_string()`. Untuk keamanan penuh gunakan prepared statements.
- **Password plaintext** — implementasi login asli menyimpan password tanpa hash. Disarankan migrasi ke `password_hash()` / `password_verify()`.
- **Kredensial DB di repo** — jangan commit `koneksi.php` dengan kredensial nyata ke repo publik. Tambahkan `koneksi.php` ke `.gitignore` dan buat `koneksi.example.php` sebagai template.
- **`$id_kasir`** selalu di-cast ke `(int)` sebelum dimasukkan ke query untuk mencegah injection.

Lihat `ROADMAP_UPGRADE.md` untuk detail teknis lengkap.

---

## Kontribusi & Branch

| Branch | Deskripsi |
|---|---|
| `master` | Versi stabil |
| `feature/upgrade-v2-security` | Upgrade aktif: dashboard baru, grafik, modul v2 |

---

## Lisensi

Proyek ini dikembangkan untuk kebutuhan internal bengkel. Silakan gunakan dan modifikasi sesuai kebutuhan.
