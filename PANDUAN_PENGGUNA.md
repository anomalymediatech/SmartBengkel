# PANDUAN PENGGUNA SISTEM INFORMASI BENGKEL v2.0

Dokumen ini menjelaskan cara menggunakan setiap modul baru yang telah ditambahkan ke sistem.

---

## DAFTAR ISI

1. [Modul Kasir & Transaksi (POS)](#1-modul-kasir--transaksi-pos)
2. [Modul Digital & Berbagi (WhatsApp & Cetak)](#2-modul-digital--berbagi-whatsapp--cetak)
3. [Modul Manajemen Data Master & Excel](#3-modul-manajemen-data-master--excel)
4. [Modul Keuangan, Hutang/Piutang & Alert](#4-modul-keuangan-hutangpiutang--alert)
5. [Modul SDM: Penggajian & Lembur](#5-modul-sdm-penggajian--lembur)
6. [Modul Laporan & Dashboard](#6-modul-laporan--dashboard)

---

## 1. MODUL KASIR & TRANSAKSI (POS)

### 1.1 Membuat Transaksi Baru

**Menu:** Transaksi → Transaksi Baru (v2)

**Langkah-langkah:**
1. Pilih barang/jasa dari dropdown "Item"
2. Masukkan jumlah dan diskon per unit (jika ada)
3. Klik tombol "+ Tambah" untuk memasukkan ke keranjang
4. Ulangi untuk semua item yang akan dibeli
5. Pilih konsumen (atau "Umum" jika pelanggan kasual)
6. Isi plat nomor kendaraan (opsional)
7. Pilih metode pembayaran:
   - **Tunai:** Pembayaran langsung
   - **Transfer:** Pembayaran via transfer bank
   - **Hutang:** Pembayaran ditangguhkan (bon)
8. Jika Hutang, isi tanggal jatuh tempo
9. Masukkan uang yang dibayar
10. Klik "Simpan & Cetak Struk"

### 1.2 Melihat Daftar Transaksi

**Menu:** Transaksi → Data Transaksi (v2)

**Fitur:**
- Filter berdasarkan tanggal dan metode pembayaran
- Lihat status pembayaran (Lunas/Belum Lunas)
- Klik "Detail" untuk melihat rincian item
- Klik "Cetak" untuk mencetak struk

### 1.3 Melihat Detail Transaksi

**Akses:** Klik tombol "Detail" dari daftar transaksi

**Informasi yang ditampilkan:**
- Header: No. nota, tanggal, konsumen, plat nomor, kasir
- Status pembayaran dan jatuh tempo
- Daftar item dengan harga, diskon, dan subtotal
- Ringkasan pembayaran

---

## 2. MODUL DIGITAL & BERBAGI (WHATSAPP & CETAK)

### 2.1 Mencetak Struk Thermal

**Ukuran:** 58mm (default) atau 80mm

**Langkah:**
1. Dari halaman detail transaksi, klik "Cetak Struk Thermal"
2. Browser akan membuka dialog print
3. Pilih printer thermal yang terhubung
4. Klik Print

**Tips:** Untuk printer 80mm, ubah CSS di `struk_thermal.php` dari `58mm` ke `80mm`

### 2.2 Mengirim Struk via WhatsApp

**Langkah:**
1. Pastikan data pelanggan memiliki nomor WA
2. Dari halaman detail transaksi, klik "Kirim ke WhatsApp"
3. Sistem akan membuka wa.me dengan pesan yang sudah terformat
4. Klik "Send" di WhatsApp

---

## 3. MODUL MANAJEMEN DATA MASTER & EXCEL

### 3.1 Import Barang dari CSV

**Menu:** Master Data → Import Barang

**Langkah:**
1. Download template CSV dengan klik "Download Template"
2. Buka file CSV di Excel/Spreadsheet
3. Isi data barang sesuai format:
   - Kolom 1: Nama barang
   - Kolom 2: Jenis (barang/jasa)
   - Kolom 3: Stok
   - Kolom 4: Harga jual
   - Kolom 5: Harga modal (opsional)
   - Kolom 6: Stok minimum (opsional, default 5)
   - Kolom 7: Keterangan (opsional)
4. Simpan file CSV
5. Upload file melalui form Import
6. Klik "Import CSV"

**Catatan:** Pastikan format CSV menggunakan koma (,) sebagai pemisah

### 3.2 Menambah Pelanggan Baru

**Menu:** Data Konsumen → Tambah Konsumen (v2)

**Data yang perlu diisi:**
- Nama pelanggan
- Nomor telepon
- Nomor WhatsApp (format: 628xxx)
- Plat nomor kendaraan
- Alamat

---

## 4. MODUL KEUANGAN, HUTANG/PIUTANG & ALERT

### 4.1 Mengelola Piutang Pelanggan

**Menu:** Hutang/Piutang → Piutang Pelanggan

**Fitur:**
- Daftar semua piutang yang belum lunas
- Total piutang yang harus ditagih
- Tombol "Bayar" untuk mencatat pembayaran
- Link WhatsApp untuk menagih langsung
- Riwayat pembayaran piutang

**Langkah mencatat pembayaran:**
1. Klik tombol "Bayar" pada baris piutang
2. Masukkan jumlah pembayaran
3. Klik "Catat Pembayaran"
4. Status akan otomatis berubah ke "Lunas" jika sudah lunas

### 4.2 Mengelola Hutang Supplier

**Menu:** Hutang/Piutang → Hutang Supplier

**Fitur:**
- Catat hutang baru ke supplier
- Daftar hutang yang belum lunas
- Catat pembayaran hutang
- Riwayat pembayaran

**Langkah mencatat hutang baru:**
1. Klik form "Catat Hutang Baru"
2. Pilih supplier
3. Isi tanggal, keterangan, total, dan jatuh tempo
4. Klik "Catat Hutang"

### 4.3 Alert Jatuh Tempo

Alert akan muncul otomatis di dashboard jika ada:
- Piutang pelanggan yang mendekati/melewati jatuh tempo (≤3 hari)
- Hutang supplier yang mendekati/melewati jatuh tempo

Alert akan berkedip merah untuk menarik perhatian.

---

## 5. MODUL SDM: PENGAJIAN & LEMBUR

### 5.1 Manajemen Data Pegawai

**Menu:** Master Data → Data Pegawai (dari sidebar)

**Fitur:**
- Tambah pegawai baru
- Edit data pegawai
- Nonaktifkan pegawai (soft delete)

**Data yang dicatat:**
- Nama dan jabatan (Mekanik/Kasir/Admin)
- Nomor WhatsApp
- Gaji pokok
- Tarif lembur per jam

### 5.2 Membuat Slip Gaji

**Menu:** SDM → Slip Gaji

**Langkah:**
1. Klik "Buat Slip Gaji Baru"
2. Pilih pegawai
3. Isi periode gaji (contoh: "Juli 2026")
4. Masukkan jam lembur dan potongan (jika ada)
5. Klik "Buat Slip Gaji"

**Perhitungan otomatis:**
- Bonus Lembur = Jam Lembur × Tarif Lembur
- Gaji Bersih = Gaji Pokok + Bonus Lembur - Potongan

### 5.3 Cetak & Kirim Slip Gaji

Setelah membuat slip:
- Klik "Cetak/Lihat" untuk preview dan cetak
- Klik "Kirim via WhatsApp" untuk kirim ke nomor WA pegawai

---

## 6. MODUL LAPORAN & DASHBOARD

### 6.1 Dashboard Baru

**Menu:** Dashboard (halaman utama)

**Informasi yang ditampilkan:**
- Omset hari ini
- Jumlah transaksi hari ini
- Item terjual hari ini
- Jumlah pelanggan hari ini
- Grafik penjualan bulan ini (Chart.js)

### 6.2 Laporan Laba Kotor

**Menu:** Laporan → Laporan Laba Kotor

**Fitur:**
- Filter periode (tanggal dari - sampai)
- Total pendapatan
- Total modal (HPP)
- Laba kotor
- Rincian per transaksi

**Catatan:** Laporan akurat hanya untuk transaksi setelah upgrade v3

### 6.3 Laporan Stok Menipis

**Menu:** Laporan → Laporan Stok Menipis

**Fitur:**
- Daftar barang yang stoknya ≤ stok minimum
- Warna kuning: stok menipis
- Warna merah: stok habis (0)

**Kegunaan:** Untuk planning pembelian ulang ke supplier

---

## TROUBLESHOOTING

### Alert tidak muncul di dashboard
- Pastikan `alert_jatuh_tempo.php` sudah di-include di `index.php`
- Periksa CSS `.alert-blink` di `custom.css`

### Import CSV gagal
- Pastikan format file CSV (bukan Excel .xlsx)
- Periksa pemisah menggunakan koma (,)
- Pastikan header sesuai template

### Laporan laba tidak akurat
- Pastikan `upgrade_v3.sql` sudah dijalankan
- Transaksi baru setelah upgrade akan memiliki snapshot harga modal
- Transaksi lama tidak memiliki data harga modal

### Grafik tidak tampil
- Pastikan ada transaksi di bulan berjalan
- Periksa koneksi internet (Chart.js dimuat dari CDN)

---

## KONTAK SUPPORT

Untuk bantuan teknis, hubungi developer atau lihat dokumentasi teknis di `ROADMAP_UPGRADE.md`.
