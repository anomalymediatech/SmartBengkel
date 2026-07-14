# Roadmap Upgrade — Sistem Informasi Bengkel

Dokumen ini menjelaskan hasil analisis repo `Farizal97/Sistem-Informasi-bengkel`, strategi upgrade yang saya pilih, cara memasang paket ini, dan rencana implementasi 6 modul yang Anda minta — lengkap dengan potongan kode untuk modul yang belum saya tulis penuh.

---

## 1. Apa yang saya temukan di codebase

**Stack:** PHP prosedural murni (gaya PHP 5.6), `mysqli`, MySQL/MariaDB, template **SB Admin 2 (Bootstrap 3)**, jQuery + DataTables + CKEditor. **Tanpa framework, tanpa Composer, tanpa MVC.** Setiap aksi = satu file `.php` (`barang_insert.php`, `trx_baru.php`, dst).

**Tabel awal:** `admin`, `kasir`, `barangjasa` (barang & jasa digabung via kolom `jenis`), `konsumen`, `supplier`, `trx` (header), `tmp_trx` (keranjang), `trxbarang` (barang masuk).

**Tiga masalah penting yang harus Anda sadari sebelum menambah fitur di atasnya:**

1. **SQL Injection di hampir semua query.** Query dirakit dengan menyambung string mentah, mis. di `login_auth.php`:
   `... WHERE user_adm='$username' AND pass_adm='$password'`. Ini bisa ditembus. Kode baru saya sudah pakai `intval()` + `mysqli_real_escape_string()` sebagai mitigasi minimal, tapi kode lama tetap rentan.
2. **Password disimpan plaintext.** Di DB: `pass_adm='admin'`, `pass_kasir='password'`. Siapa pun yang melihat DB langsung tahu password.
3. **Kredensial DB ter-commit** di `dist/config/koneksi.php` dan `kasir/koneksi.php` (user/pass database ada di dalam repo publik). Ini pola yang sama dengan isu `.env` yang pernah Anda temui di SAHAIA — sebaiknya rotasi kredensial DB dan jangan commit file koneksi ke repo publik.

Ada juga bug desain: **`harga`/`stok` bertipe VARCHAR**, dan **detail transaksi lama dibaca ulang dari harga barang terkini** — jadi kalau harga barang diubah, struk lama ikut berubah. Upgrade ini memperbaikinya lewat tabel `trx_detail` dengan *snapshot* harga.

---

## 2. Strategi upgrade: NON-DESTRUKTIF

Karena Anda ingin *menambah fitur pada repo ini* (bukan menulis ulang), saya pilih pendekatan yang paling aman untuk "vibe coding":

- **SQL hanya menambah** kolom & tabel baru (`upgrade_v2.sql`) — data lama utuh.
- **File PHP baru diberi akhiran `_v2`** sehingga tidak menimpa file lama. Anda bisa uji berdampingan, lalu ganti link menu bila sudah yakin.

> Alternatif yang lebih bersih jangka panjang (tapi lebih besar) adalah migrasi ke Laravel — yang kebetulan sudah jadi stack backend SAHAIA Anda. Kalau bengkel ini akan tumbuh jadi produk, migrasi ke Laravel + Filament menghilangkan seluruh utang keamanan di atas sekaligus. Untuk sekarang, paket ini menjaga Anda tetap di jalur repo asli.

---

## 3. Cara memasang

1. **Backup database** (export via phpMyAdmin).
2. Jalankan **`sql/upgrade_v2.sql`** (phpMyAdmin → SQL, atau `mysql -u USER -p NAMA_DB < upgrade_v2.sql`).
3. Salin file dari folder `kasir/` paket ini ke folder `kasir/` di repo Anda:
   `trx_baru_v2.php`, `struk_thermal.php`, `kirim_wa.php`.
4. Tambahkan link menu. Buka `kasir/layout_top.php`, cari blok menu sidebar, tambahkan:
   ```html
   <li><a href="trx_baru_v2.php"><i class="fa fa-cart-plus"></i> Transaksi Baru (v2)</a></li>
   ```
5. Uji: buka **Transaksi Baru (v2)**, tambah barang + jasa, beri diskon per item, pilih metode bayar, Simpan → otomatis ke struk thermal → uji Cetak & Kirim WA.

---

## 4. Status per modul

Legenda: ✅ selesai (kode disertakan) · 🟡 skema siap, kode tinggal dibuat · ⬜ berikutnya

### Modul 1 — Kasir & Transaksi ✅
- ✅ Nota multi-item (barang + jasa dalam satu nota)
- ✅ Diskon per item (kolom `diskon` di `tmp_trx` & `trx_detail`)
- ✅ Subtotal otomatis real-time (JS di `trx_baru_v2.php`)
- ✅ Metode bayar Tunai / Transfer / Hutang (kolom `metode_bayar`)
- 🟡 Riwayat & detail transaksi: header `trx` sudah menyimpan semua field baru dan detail ada di `trx_detail`. **TODO kecil:** buat `trx_v2.php` (daftar) & `trx_detail_v2.php` yang membaca dari `trx_detail` (bukan `tmp_trx` lama). Query intinya:
  ```php
  // daftar transaksi
  SELECT trx.*, konsumen.nama_kon FROM trx
  LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon ORDER BY trx.tgl_trx DESC;
  // detail satu nota
  SELECT * FROM trx_detail WHERE id_trx='$kode';
  ```

### Modul 2 — WhatsApp & Cetak ✅
- ✅ Cetak struk thermal 58mm (`struk_thermal.php`, CSS `@page size:58mm auto`) — cocok untuk mini printer Bluetooth/USB via dialog print browser.
- ✅ Kirim struk via WhatsApp (`kirim_wa.php`) — nomor dinormalisasi ke format `62...`, teks nota di-`rawurlencode` ke `https://wa.me/`.
- Untuk printer 80mm, ubah `58mm` → `80mm` di CSS `struk_thermal.php`.

### Modul 3 — Data Master & Excel 🟡
Skema siap. Yang perlu dibuat:
- **Pelanggan + plat nomor:** kolom `plat_nomor`, `wa_kon` sudah ada di `konsumen`; tambahkan input tersebut di `konsumen_tambah.php`. Riwayat servis per plat: tabel `kendaraan` sudah ada.
- **Import Excel sparepart:** dua opsi.
  - *Paling ringan (tanpa Composer):* minta template **CSV**, parse dengan `fgetcsv()`:
    ```php
    if(isset($_FILES['file'])){
      $h = fopen($_FILES['file']['tmp_name'],'r'); fgetcsv($h); // skip header
      while(($row=fgetcsv($h))!==false){
        $nama=mysqli_real_escape_string($conn,$row[0]);
        $jenis=mysqli_real_escape_string($conn,$row[1]);
        $stok=intval($row[2]); $harga=intval($row[3]); $modal=intval($row[4]);
        mysqli_query($conn,"INSERT INTO barangjasa(nama,jenis,stok,harga,harga_modal,keterangan,id_adm)
          VALUES('$nama','$jenis','$stok','$harga','$modal','','1')");
      }
    }
    ```
  - *Excel asli (.xlsx):* pasang **PhpSpreadsheet** (`composer require phpoffice/phpspreadsheet`) lalu `IOFactory::load()`. Lebih kuat tapi butuh Composer di hosting.
- **Manajemen jasa / supplier / user:** sudah ada di repo asli (`jasa.php`, `supplier.php`, `kasir.php`).

### Modul 4 — Hutang / Piutang & Alert 🟡
Skema siap (`bayar_piutang`, `hutang_supplier`, `bayar_hutang`).
- **Piutang pelanggan** = transaksi `metode_bayar='Hutang'` yang belum lunas:
  ```php
  SELECT trx.*, konsumen.nama_kon,
    (trx.total - trx.bayar - IFNULL((SELECT SUM(jumlah) FROM bayar_piutang b WHERE b.id_trx=trx.id_trx),0)) AS sisa
  FROM trx LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon
  WHERE trx.metode_bayar='Hutang' AND trx.status_bayar='Belum Lunas';
  ```
  Saat pelanggan mencicil → `INSERT INTO bayar_piutang`, lalu bila sisa=0 update `trx.status_bayar='Lunas'`.
- **Hutang supplier**: form input `hutang_supplier`, pelunasan via `bayar_hutang`.
- **Alert jatuh tempo berkedip** di dashboard:
  ```php
  $al = mysqli_query($conn,"SELECT COUNT(*) c FROM trx
    WHERE metode_bayar='Hutang' AND status_bayar='Belum Lunas'
    AND jatuh_tempo IS NOT NULL AND jatuh_tempo <= CURDATE() + INTERVAL 3 DAY");
  $n = mysqli_fetch_array($al)['c'];
  if($n>0) echo '<div class="alert-blink">⚠️ '.$n.' piutang mendekati/lewat jatuh tempo!</div>';
  ```
  ```css
  .alert-blink{background:#d9534f;color:#fff;padding:12px;border-radius:6px;
    animation:blink 1s step-start infinite;font-weight:bold;}
  @keyframes blink{50%{opacity:.35;}}
  ```

### Modul 5 — SDM: Penggajian & Lembur 🟡
Skema siap (`pegawai`, `slip_gaji`).
- CRUD `pegawai` (nama, jabatan, gaji_pokok, tarif_lembur, wa).
- **Kalkulator gaji bersih** = `gaji_pokok + (jam_lembur * tarif_lembur) - potongan`. Hitung di server saat simpan slip:
  ```php
  $bonus = $jam_lembur * $tarif_lembur;
  $bersih = $gaji_pokok + $bonus - $potongan;
  // INSERT INTO slip_gaji (...)
  ```
- **Cetak / kirim slip via WA**: pola sama persis dengan `struk_thermal.php` + `kirim_wa.php` — tinggal ganti sumber data ke `slip_gaji` dan nomor ke `pegawai.wa`.

### Modul 6 — Laporan & Dashboard 🟡
- **Dashboard grafik** (omset harian + grafik penjualan bulan ini): pakai **Chart.js**. Data:
  ```php
  SELECT DATE(tgl_trx) tgl, SUM(total) omset FROM trx
  WHERE MONTH(tgl_trx)=MONTH(CURDATE()) AND YEAR(tgl_trx)=YEAR(CURDATE())
  GROUP BY DATE(tgl_trx);
  ```
  Ubah ke array JSON → `new Chart(ctx,{type:'line',data:{...}})`.
- **Laba kotor/bersih**: sekarang bisa dihitung karena ada `harga_modal`.
  `laba_kotor = SUM(subtotal) - SUM(harga_modal_snapshot * jml)`. (Catatan: untuk akurat, snapshot `harga_modal` juga di `trx_detail` — tambahkan kolom bila perlu.)
- **Laporan stok menipis** (reorder): `stok_min` sudah ada.
  ```php
  SELECT * FROM barangjasa WHERE jenis='barang' AND CAST(stok AS UNSIGNED) <= stok_min;
  ```

---

## 5. Keamanan (sangat disarankan sebelum go-live)

1. **Hash password.** Ubah `login_auth.php` agar verifikasi pakai `password_verify()`, dan saat buat/ubah user pakai `password_hash($pass, PASSWORD_DEFAULT)`. Migrasi user lama: minta reset password sekali, atau update satu per satu.
2. **Pindahkan kredensial DB** keluar dari repo. Buat `koneksi.php` dari `koneksi.example.php` dan tambahkan `koneksi.php` ke `.gitignore`. Rotasi user/password DB yang sudah terlanjur publik.
3. **Prepared statements** untuk semua query baru: `mysqli_prepare` + `bind_param`. File `_v2` saya sudah memitigasi via `intval()`/escape, tapi prepared statement lebih baik.

---

## 6. Ringkas: apa yang sudah jadi vs berikutnya

| Modul | Status | Sisa pekerjaan |
|---|---|---|
| 1. POS (diskon item, metode bayar, snapshot) | ✅ | `trx_v2.php` daftar + detail dari `trx_detail` |
| 2. WA + Cetak thermal | ✅ | (opsional) sesuaikan lebar 80mm |
| 3. Master + Import Excel | 🟡 | halaman import CSV/xlsx, input plat & kendaraan |
| 4. Hutang/Piutang + Alert | 🟡 | halaman piutang, hutang supplier, widget alert |
| 5. Penggajian | 🟡 | CRUD pegawai, form slip, cetak/WA slip |
| 6. Dashboard/Laporan | 🟡 | grafik Chart.js, laporan laba, laporan reorder |

Skema database untuk **keenam modul sudah lengkap** di `upgrade_v2.sql`, jadi sisa pekerjaan tinggal halaman UI + query yang polanya sudah saya berikan di atas.
