# Design Document — Dashboard + Grafik (Modul 6)

## Overview

Dokumen ini mendeskripsikan desain teknis peningkatan halaman dashboard SmartBengkel dari 2 widget sederhana menjadi dashboard informasi lengkap. Peningkatan mencakup widget statistik harian yang lebih kaya, grafik penjualan bulanan berbasis Chart.js, alert jatuh tempo piutang/hutang, widget stok menipis, dan widget laba kotor.

Seluruh implementasi mengikuti stack yang ada: **PHP prosedural murni, `mysqli`, Bootstrap 3 (SB Admin 2), jQuery** — tanpa framework, tanpa Composer. Tidak ada perubahan skema database.

### File yang Dibuat/Diganti

| File | Aksi | Deskripsi |
|---|---|---|
| `index.php` | Replace | Dashboard Admin baru |
| `dashboard_data.php` | Buat baru | Endpoint AJAX JSON untuk grafik Admin |
| `kasir/index.php` | Replace | Dashboard Kasir baru |
| `kasir/dashboard_data.php` | Buat baru | Endpoint AJAX JSON untuk grafik Kasir |

---

## Architecture

Dashboard menggunakan pola **Server-Side Render + AJAX Lazy Load** yang sudah umum di codebase ini:

```
Browser
  │
  ├── GET index.php
  │     ├── include sess_check.php        ← auth guard (eksekusi pertama)
  │     ├── PHP query: widget statistik   ← render sinkron (inline PHP)
  │     ├── include alert_jatuh_tempo.php ← render kondisional
  │     ├── include layout_top.php        ← HTML shell + sidebar
  │     ├── HTML: widget statistik (rendered)
  │     ├── HTML: <canvas id="salesChart">← placeholder kosong
  │     └── JS: $.getJSON("dashboard_data.php", callback)
  │
  └── GET dashboard_data.php (via AJAX)
        ├── include sess_check.php        ← auth guard
        ├── PHP query: omset per hari
        └── echo json_encode([...])       ← JSON response
```

**Keputusan desain:**
- Widget statistik dirender server-side (sinkron) agar tidak ada flash of empty content saat halaman pertama kali dimuat.
- Data grafik diambil via AJAX untuk memisahkan concern antara layout dan data visualisasi, serta agar grafik bisa direfresh tanpa reload halaman di masa depan.
- `dashboard_data.php` adalah file PHP terpisah (bukan endpoint REST baru) sesuai konvensi "satu aksi = satu file PHP" yang ada.

---

## Components and Interfaces

### 1. `index.php` (Dashboard Admin)

**Urutan eksekusi:**
1. `include("sess_check.php")` — auth guard, halt jika tidak terautentikasi
2. `include("dist/function/format_rupiah.php")` — fungsi format
3. Query PHP sinkron untuk semua widget statistik
4. Set `$pagedesc = "Beranda"`, `include("layout_top.php")`
5. Render HTML: alert, widget statistik (4 kolom + laba kotor), panel stok menipis
6. Render `<canvas>` untuk grafik
7. Script JS: `$.getJSON("dashboard_data.php", ...)` → render Chart.js
8. `include("layout_bottom.php")`

**Query yang dijalankan (sinkron):**

```php
// Omset & jumlah transaksi hari ini
$sql_stats = "SELECT COUNT(id_trx) AS jml_trx, COALESCE(SUM(total),0) AS omset
              FROM trx WHERE tgl_trx = CURDATE() AND status_bayar != 'Batal'";

// Item terjual hari ini
$sql_items = "SELECT COALESCE(SUM(td.jml),0) AS item_terjual
              FROM trx_detail td
              JOIN trx t ON td.id_trx = t.id_trx
              WHERE t.tgl_trx = CURDATE() AND t.status_bayar != 'Batal'";

// Pelanggan unik hari ini (kecualikan id_kon = 0 / Umum)
$sql_pelanggan = "SELECT COUNT(DISTINCT id_kon) AS pelanggan_unik
                  FROM trx WHERE tgl_trx = CURDATE()
                  AND id_kon != 0 AND status_bayar != 'Batal'";

// Laba kotor hari ini (dengan fallback jika harga_modal_snap tidak ada)
$sql_laba = "SELECT COALESCE(SUM(td.subtotal),0) - COALESCE(SUM(td.harga_modal_snap * td.jml),0) AS laba_kotor
             FROM trx_detail td
             JOIN trx t ON td.id_trx = t.id_trx
             WHERE t.tgl_trx = CURDATE() AND t.status_bayar != 'Batal'";

// Stok menipis (maksimal 5)
$sql_stok = "SELECT nama, stok, stok_min FROM barangjasa
             WHERE jenis='barang' AND CAST(stok AS UNSIGNED) <= stok_min
             ORDER BY CAST(stok AS UNSIGNED) ASC LIMIT 5";
```

**Fallback untuk kolom opsional:**
- `harga_modal_snap`: query laba kotor dibungkus `@$result = mysqli_query(...)`. Jika `mysqli_query` return false (kolom tidak ada), widget laba kotor menampilkan `Rp 0`.
- `stok_min`: query stok menipis dibungkus serupa. Jika gagal, blok panel stok menipis dilewati.

### 2. `dashboard_data.php` (AJAX Endpoint Admin)

**Input:** tidak ada parameter GET/POST (menggunakan sesi yang sudah ada)

**Output:** `Content-Type: application/json`

```json
{
  "labels": ["1", "2", "3", ..., "31"],
  "data": [150000, 0, 275000, ..., 0]
}
```

**Logika pengisian gap (hari tanpa transaksi):**

```php
// Ambil data dari DB
$sql = "SELECT DAY(tgl_trx) AS hari, SUM(total) AS omset
        FROM trx
        WHERE MONTH(tgl_trx) = MONTH(CURDATE())
          AND YEAR(tgl_trx)  = YEAR(CURDATE())
          AND status_bayar != 'Batal'
        GROUP BY DAY(tgl_trx)";

// Bangun lookup: [hari => omset]
$lookup = [];
while ($row = mysqli_fetch_assoc($res)) {
    $lookup[(int)$row['hari']] = (float)$row['omset'];
}

// Isi array 1..N hari dalam bulan, default 0
$days_in_month = (int)date('t');
$labels = [];
$data   = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $labels[] = (string)$d;
    $data[]   = isset($lookup[$d]) ? $lookup[$d] : 0.0;
}
```

**Semua nilai numerik dikembalikan sebagai `int`/`float`, bukan string.**

### 3. `kasir/index.php` (Dashboard Kasir)

Struktur sama seperti Dashboard Admin tetapi:
- Semua query difilter oleh `$_SESSION['kasir_id']` (nama variabel sesi mengikuti `sess_check.php` kasir yang ada).
- Widget: Transaksi Hari Ini, Item Terjual, Omset Saya Hari Ini (3 widget, bukan 5).
- **Tidak ada**: widget laba kotor, panel stok menipis, `include("alert_jatuh_tempo.php")`.
- Grafik bertipe `bar` (bukan `line`), data = jumlah transaksi per hari (bukan omset).

**Query kasir (sinkron):**

```php
$id_kasir = (int)$_SESSION['kasir_id']; // cast ke int untuk safety

// Transaksi & omset kasir hari ini
$sql_kasir = "SELECT COUNT(id_trx) AS jml_trx, COALESCE(SUM(total),0) AS omset
              FROM trx
              WHERE tgl_trx = CURDATE()
                AND id_kasir = $id_kasir
                AND status_bayar != 'Batal'";

// Item terjual kasir hari ini
$sql_items_kasir = "SELECT COALESCE(SUM(td.jml),0) AS item_terjual
                    FROM trx_detail td
                    JOIN trx t ON td.id_trx = t.id_trx
                    WHERE t.tgl_trx = CURDATE()
                      AND t.id_kasir = $id_kasir
                      AND t.status_bayar != 'Batal'";
```

### 4. `kasir/dashboard_data.php` (AJAX Endpoint Kasir)

Serupa dengan `dashboard_data.php` admin tetapi:
- Query difilter `AND id_kasir = $id_kasir`.
- `data` array berisi **jumlah transaksi** (`COUNT(id_trx)`) per hari, bukan omset.

```json
{
  "labels": ["1", "2", ..., "31"],
  "data": [3, 0, 5, ..., 1]
}
```

---

## Data Models

Dashboard tidak menambahkan atau mengubah skema database. Tabel yang digunakan:

### Tabel `trx`

| Kolom | Tipe | Digunakan untuk |
|---|---|---|
| `id_trx` | INT PK | COUNT transaksi |
| `tgl_trx` | DATE | Filter tanggal hari ini / bulan ini |
| `total` | DECIMAL | SUM omset |
| `id_kasir` | INT | Filter per kasir |
| `id_kon` | INT | COUNT pelanggan unik (kecuali 0) |
| `status_bayar` | VARCHAR | Filter (`!= 'Batal'`) |
| `metode_bayar` | VARCHAR | Digunakan `alert_jatuh_tempo.php` |
| `jatuh_tempo` | DATE | Digunakan `alert_jatuh_tempo.php` |

### Tabel `trx_detail`

| Kolom | Tipe | Digunakan untuk |
|---|---|---|
| `id_trx` | INT FK | JOIN ke `trx` |
| `jml` | INT | SUM item terjual |
| `subtotal` | DECIMAL | Komponen laba kotor |
| `harga_modal_snap` | DECIMAL | Komponen laba kotor (opsional) |

### Tabel `barangjasa`

| Kolom | Tipe | Digunakan untuk |
|---|---|---|
| `nama` | VARCHAR | Tampil di panel stok menipis |
| `stok` | VARCHAR | Dibanding stok_min (CAST ke UNSIGNED) |
| `stok_min` | INT | Ambang batas stok menipis (opsional) |
| `jenis` | VARCHAR | Filter `= 'barang'` |

### Tabel `hutang_supplier`

Digunakan oleh `alert_jatuh_tempo.php` yang sudah ada (tidak dimodifikasi).

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Fitur ini melibatkan logika PHP murni (query aggregation, format fungsi, conditional rendering, JSON serialization) yang cocok untuk property-based testing. Library yang digunakan: **[QuickCheck for PHP](https://github.com/steos/php-quickcheck)** atau alternatif sederhana dengan generator loop manual karena proyek tidak menggunakan Composer — property test akan diimplementasikan sebagai skrip PHP CLI dengan generator data acak.

---

### Property 1: format_rupiah selalu menghasilkan string bertipe Rupiah

*For any* nilai numerik non-negatif (int atau float), fungsi `format_rupiah()` harus mengembalikan string yang diawali dengan "Rp" diikuti angka yang diformat dengan pemisah ribuan titik (`.`) dan tidak mengandung karakter selain digit, titik, dan prefix "Rp".

**Validates: Requirements 1.1, 2.3**

---

### Property 2: Isolasi data kasir pada widget statistik

*For any* `id_kasir` yang valid, nilai semua widget statistik di Dashboard Kasir (jumlah transaksi, item terjual, omset) harus dihitung **hanya** dari transaksi yang memiliki `id_kasir` cocok — tidak ada kontribusi dari transaksi kasir lain atau kasir bernilai 0.

**Validates: Requirements 2.1, 2.2, 2.3**

---

### Property 3: JSON dashboard_data memiliki struktur kontinu dan lengkap

*For any* bulan kalender, endpoint `dashboard_data.php` harus mengembalikan JSON di mana:
- Panjang array `labels` tepat sama dengan jumlah hari dalam bulan tersebut.
- Panjang array `data` sama dengan panjang `labels`.
- Setiap posisi `data[i]` yang tidak memiliki transaksi di database berisi nilai `0.0` (float), bukan `null` atau string kosong.
- Setiap elemen `data` bertipe `int` atau `float`, bukan string.

**Validates: Requirements 3.3, 3.4, 8.5**

---

### Property 4: Isolasi data kasir pada grafik batang

*For any* `id_kasir` yang valid, setiap nilai dalam array `data` yang dikembalikan oleh `kasir/dashboard_data.php` harus merepresentasikan **hanya** transaksi kasir tersebut — tidak pernah mencakup transaksi kasir lain.

**Validates: Requirements 4.2**

---

### Property 5: Visibilitas alert jatuh tempo berkorelasi tepat dengan data

*For any* kumpulan record piutang/hutang dengan berbagai tanggal `jatuh_tempo`:
- Alert **harus tampil** jika dan hanya jika terdapat minimal satu record dengan `jatuh_tempo <= CURDATE() + INTERVAL 3 DAY` dan `status_bayar = 'Belum Lunas'`.
- Alert **tidak boleh tampil** jika semua record memiliki `jatuh_tempo > CURDATE() + INTERVAL 3 DAY` atau tidak ada record sama sekali.

**Validates: Requirements 5.1, 5.2**

---

### Property 6: Panel stok menipis hanya menampilkan barang yang memenuhi kondisi dan dibatasi 5

*For any* kumpulan record `barangjasa`, daftar yang ditampilkan di panel Stok Menipis harus memenuhi dua kondisi secara bersamaan:
1. Setiap item yang tampil harus memiliki `CAST(stok AS UNSIGNED) <= stok_min`.
2. Jumlah item yang tampil tidak boleh melebihi 5, diurut dari nilai stok terkecil.

**Validates: Requirements 6.1, 6.3**

---

### Property 7: Kalkulasi laba kotor akurat secara aritmetika

*For any* kumpulan record `trx_detail` dengan nilai `subtotal` dan `harga_modal_snap * jml` yang bervariasi, nilai laba kotor yang dihitung harus tepat sama dengan `SUM(subtotal) - SUM(harga_modal_snap * jml)` dengan presisi floating-point yang wajar (toleransi ±0.01 Rupiah akibat pembulatan).

**Validates: Requirements 7.1**

---

### Property 8: Warna panel laba kotor mencerminkan tanda nilai

*For any* nilai laba kotor yang dikalkulasi:
- Jika nilai > 0, kelas CSS panel yang digunakan harus `panel-green`.
- Jika nilai ≤ 0, kelas CSS panel yang digunakan harus `panel-red`.

Tidak ada nilai laba kotor yang boleh menghasilkan kelas panel selain kedua kelas tersebut.

**Validates: Requirements 7.2, 7.3**

---

### Property 9: Authentication gate mencegah akses tanpa sesi valid

*For any* HTTP request ke `index.php` atau `dashboard_data.php` (baik admin maupun kasir) yang tidak disertai sesi valid, sistem harus:
1. Mengembalikan HTTP redirect ke `login.php` (header Location).
2. Tidak mengembalikan data apapun (widget HTML, JSON, atau data query database).
3. Menghentikan eksekusi (tidak ada output setelah redirect).

**Validates: Requirements 8.3, 8.4**

---

## Error Handling

### Kolom Database Opsional

Dua kolom diperlakukan sebagai opsional karena mungkin tidak ada di semua instalasi:

| Kolom | File | Penanganan |
|---|---|---|
| `harga_modal_snap` (tabel `trx_detail`) | `index.php` | `@$res = mysqli_query(...)`. Jika `false`, tampilkan `Rp 0` pada widget laba kotor. |
| `stok_min` (tabel `barangjasa`) | `index.php` | `@$res = mysqli_query(...)`. Jika `false`, sembunyikan panel stok menipis seluruhnya. |

Operator `@` digunakan sesuai konvensi codebase yang ada untuk menekan error PHP pada query yang mungkin gagal karena skema berbeda.

### AJAX Error Handling

```javascript
$.getJSON("dashboard_data.php")
  .done(function(json) {
      // render Chart.js
  })
  .fail(function() {
      $("#chart-container").html(
          '<p class="text-danger text-center">Data grafik tidak dapat dimuat.</p>'
      );
  });
```

Pola yang sama diterapkan di `kasir/index.php` dengan endpoint `kasir/dashboard_data.php`.

### Nilai NULL dari Aggregasi

Semua query aggregasi menggunakan `COALESCE(..., 0)` untuk memastikan nilai NULL dari `SUM()` atau `COUNT()` pada result set kosong dikonversi ke 0. Ini mencegah notice PHP saat melakukan aritmetika atau formatting.

### Session Invalid

Handling dilakukan sepenuhnya oleh `sess_check.php` yang sudah ada (tidak dimodifikasi). File tersebut sudah mengimplementasikan `header('Location: login.php'); exit;`.

---

## Testing Strategy

### Dual Testing Approach

Dashboard ini melibatkan dua layer yang berbeda:
1. **PHP logic layer** (query aggregation, JSON builder, laba kotor formula) — cocok untuk property-based testing.
2. **HTML rendering layer** (widget layout, Chart.js init, CSS classes) — cocok untuk example-based testing.

### Unit Tests (Example-Based)

Fokus pada skenario konkret yang tidak tercakup property test:

| Test Case | File | Yang Diverifikasi |
|---|---|---|
| Dashboard Admin dengan data lengkap | `index.php` | Semua 5 widget muncul dengan nilai benar |
| Dashboard Admin tanpa transaksi hari ini | `index.php` | Semua widget menampilkan 0, tidak ada PHP error |
| Widget laba kotor — tanpa kolom harga_modal_snap | `index.php` | Tampil `Rp 0`, halaman tidak crash |
| Panel stok menipis — tanpa kolom stok_min | `index.php` | Panel tersembunyi, halaman tidak crash |
| Alert jatuh tempo muncul dengan link benar | `alert_jatuh_tempo.php` | Link ke `piutang_v2.php` dan `hutang_supplier_v2.php` |
| Dashboard Kasir tidak menampilkan laba kotor | `kasir/index.php` | Tidak ada elemen widget laba kotor di HTML |
| Dashboard Kasir tidak menampilkan alert jatuh tempo | `kasir/index.php` | Tidak ada `alert-blink` di HTML kasir |
| AJAX gagal — pesan error muncul | `index.php` JS | Pesan "Data grafik tidak dapat dimuat" muncul |
| Chart.js tipe line pada admin | `index.php` | `type: 'line'` di konfigurasi Chart.js |
| Chart.js tipe bar pada kasir | `kasir/index.php` | `type: 'bar'` di konfigurasi Chart.js |

### Property-Based Tests

Karena proyek tidak menggunakan Composer, property test diimplementasikan sebagai **skrip PHP CLI dengan generator data sederhana** — sebuah loop yang menghasilkan N sampel acak dan menjalankan assertion pada tiap sampel (minimum 100 iterasi).

Setiap test dikomentar dengan tag referensi:
```php
// Feature: dashboard-grafik, Property 1: format_rupiah selalu menghasilkan string bertipe Rupiah
```

**Implementasi tiap property:**

| Property | File Test | Generator Input | Assertion |
|---|---|---|---|
| P1: format_rupiah | `tests/prop_format_rupiah.php` | Random int/float 0–999_999_999 | Output starts with "Rp", contains digits and dots only |
| P2: Isolasi data kasir | `tests/prop_kasir_isolation.php` | Random kasir_id, random trx data | Widget values match only matching id_kasir |
| P3: JSON struktur kontinu | `tests/prop_dashboard_data_json.php` | Random month/year, random subset of days | labels.length == days_in_month, data.length == labels.length, all gaps are 0.0 |
| P4: Isolasi grafik kasir | `tests/prop_kasir_chart_isolation.php` | Random kasir_id, random trx data | Chart data includes only matching kasir |
| P5: Visibilitas alert | `tests/prop_alert_visibility.php` | Random jatuh_tempo dates + status_bayar | Alert shown iff any record within 3 days |
| P6: Panel stok menipis | `tests/prop_stok_menipis.php` | Random stok/stok_min combinations | Max 5 items, all satisfy CAST(stok) <= stok_min |
| P7: Kalkulasi laba kotor | `tests/prop_laba_kotor.php` | Random subtotal/harga_modal_snap/jml sets | Result == SUM(subtotal) - SUM(modal*jml) |
| P8: Warna panel laba | `tests/prop_laba_warna.php` | Random positive/zero/negative floats | Positive → panel-green, ≤0 → panel-red |
| P9: Auth gate | `tests/prop_auth_gate.php` | Requests without session | Always redirects, no data returned |

**Konfigurasi minimum:** Setiap property test berjalan minimum **100 iterasi** per eksekusi.
