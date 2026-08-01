-- =====================================================================
--  UPGRADE v2 — Sistem Informasi Bengkel
--  Menambahkan skema untuk 6 modul: POS, WA/Cetak, Master+Excel,
--  Hutang/Piutang+Alert, Penggajian, Dashboard/Laporan.
--
--  CARA PAKAI:
--    1. BACKUP dulu database Anda (export via phpMyAdmin) sebelum menjalankan.
--    2. Import file ini via phpMyAdmin > tab SQL, atau:
--         mysql -u USER -p NAMA_DB < upgrade_v2.sql
--    3. Script ini NON-DESTRUKTIF: hanya menambah kolom & tabel baru,
--       tidak menghapus data lama. Aman dijalankan 1x.
--
--  Catatan: "ADD COLUMN IF NOT EXISTS" didukung MariaDB / MySQL 8.0.1+.
--  Jika hosting Anda MySQL lama (<8.0.1) dan error, hapus "IF NOT EXISTS"
--  pada baris ALTER dan jalankan tiap ALTER hanya sekali.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ---------------------------------------------------------------------
-- MODUL 1 — KASIR & TRANSAKSI
-- ---------------------------------------------------------------------

-- Header transaksi: metode bayar, uang bayar/kembali, diskon nota,
-- status pelunasan, dan jatuh tempo (untuk Hutang/Bon).
ALTER TABLE `trx`
  ADD COLUMN IF NOT EXISTS `metode_bayar` ENUM('Tunai','Transfer','Hutang') NOT NULL DEFAULT 'Tunai',
  ADD COLUMN IF NOT EXISTS `bayar`        BIGINT      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `kembali`      BIGINT      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `diskon_nota`  BIGINT      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `status_bayar` ENUM('Lunas','Belum Lunas') NOT NULL DEFAULT 'Lunas',
  ADD COLUMN IF NOT EXISTS `jatuh_tempo`  DATE        NULL,
  ADD COLUMN IF NOT EXISTS `plat_nomor`   VARCHAR(15) NULL,
  ADD COLUMN IF NOT EXISTS `catatan`      TEXT        NULL;

-- Cart sementara: tambahkan diskon per item.
ALTER TABLE `tmp_trx`
  ADD COLUMN IF NOT EXISTS `diskon` BIGINT NOT NULL DEFAULT 0;

-- Tabel BARU: detail transaksi permanen dgn SNAPSHOT harga & diskon.
-- Ini memperbaiki bug lama: dulu detail dibaca ulang dari harga barang
-- terkini, sehingga struk lama ikut berubah bila harga diubah.
CREATE TABLE IF NOT EXISTS `trx_detail` (
  `id_detail`   INT(11)      NOT NULL AUTO_INCREMENT,
  `id_trx`      VARCHAR(20)  NOT NULL,
  `id_brg`      INT(11)      NOT NULL,
  `nama_snap`   VARCHAR(100) NOT NULL,   -- snapshot nama barang/jasa
  `jenis_snap`  VARCHAR(20)  NOT NULL,   -- 'barang' / 'jasa'
  `harga_snap`  BIGINT       NOT NULL,   -- snapshot harga satuan saat jual
  `harga_modal_snap` BIGINT  NOT NULL DEFAULT 0,  -- snapshot harga modal (utk laba)
  `diskon`      BIGINT       NOT NULL DEFAULT 0,  -- diskon per unit
  `jml`         INT(11)      NOT NULL,
  `subtotal`    BIGINT       NOT NULL,   -- (harga_snap - diskon) * jml
  PRIMARY KEY (`id_detail`),
  KEY `idx_id_trx` (`id_trx`),
  KEY `idx_id_brg` (`id_brg`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Jaga jika tabel sudah terlanjur dibuat tanpa kolom harga_modal_snap
ALTER TABLE `trx_detail`
  ADD COLUMN IF NOT EXISTS `harga_modal_snap` BIGINT NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- MODUL 3 — DATA MASTER
-- ---------------------------------------------------------------------

-- Pelanggan: nomor WA khusus + plat kendaraan default.
ALTER TABLE `konsumen`
  ADD COLUMN IF NOT EXISTS `wa_kon`      VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `plat_nomor`  VARCHAR(15) NULL;

-- Supplier: nomor WA untuk kirim orderan / konfirmasi.
ALTER TABLE `supplier`
  ADD COLUMN IF NOT EXISTS `wa_spl` VARCHAR(20) NULL;

-- Barang/Jasa: harga modal (utk laba), dan stok minimum (utk alert stok).
ALTER TABLE `barangjasa`
  ADD COLUMN IF NOT EXISTS `harga_modal` BIGINT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `stok_min`    INT(11) NOT NULL DEFAULT 5;

-- Tabel BARU: kendaraan pelanggan (1 pelanggan bisa banyak kendaraan)
-- untuk lacak riwayat servis per plat nomor.
CREATE TABLE IF NOT EXISTS `kendaraan` (
  `id_kendaraan` INT(11)     NOT NULL AUTO_INCREMENT,
  `id_kon`       INT(11)     NOT NULL,
  `plat_nomor`   VARCHAR(15) NOT NULL,
  `merk`         VARCHAR(50) NULL,
  `tipe`         VARCHAR(50) NULL,
  `tahun`        VARCHAR(4)  NULL,
  PRIMARY KEY (`id_kendaraan`),
  KEY `idx_id_kon` (`id_kon`),
  KEY `idx_plat`   (`plat_nomor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Tabel BARU: pengaturan toko (dipakai header struk & WA).
CREATE TABLE IF NOT EXISTS `toko` (
  `id_toko`    INT(11)      NOT NULL AUTO_INCREMENT,
  `nama_toko`  VARCHAR(100) NOT NULL,
  `alamat`     TEXT         NULL,
  `telp`       VARCHAR(20)  NULL,
  `footer_nota` VARCHAR(150) NULL,
  PRIMARY KEY (`id_toko`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `toko` (`id_toko`,`nama_toko`,`alamat`,`telp`,`footer_nota`)
SELECT 1,'Bengkel Mantap Jiwa','Bekasi','(021) 192819189','Terima kasih atas kunjungan Anda'
WHERE NOT EXISTS (SELECT 1 FROM `toko` WHERE `id_toko`=1);

-- ---------------------------------------------------------------------
-- MODUL 4 — HUTANG / PIUTANG
-- ---------------------------------------------------------------------

-- Pembayaran cicilan PIUTANG pelanggan (transaksi metode 'Hutang').
CREATE TABLE IF NOT EXISTS `bayar_piutang` (
  `id_bayar` INT(11)     NOT NULL AUTO_INCREMENT,
  `id_trx`   VARCHAR(20) NOT NULL,
  `tgl`      DATE        NOT NULL,
  `jumlah`   BIGINT      NOT NULL,
  `id_kasir` INT(11)     NOT NULL,
  PRIMARY KEY (`id_bayar`),
  KEY `idx_id_trx` (`id_trx`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- HUTANG bengkel ke supplier (tagihan pembelian barang).
CREATE TABLE IF NOT EXISTS `hutang_supplier` (
  `id_hutang`   INT(11)     NOT NULL AUTO_INCREMENT,
  `id_spl`      INT(11)     NOT NULL,
  `tgl`         DATE        NOT NULL,
  `keterangan`  VARCHAR(150) NULL,
  `total`       BIGINT      NOT NULL,
  `jatuh_tempo` DATE        NULL,
  `status_bayar` ENUM('Lunas','Belum Lunas') NOT NULL DEFAULT 'Belum Lunas',
  PRIMARY KEY (`id_hutang`),
  KEY `idx_id_spl` (`id_spl`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `bayar_hutang` (
  `id_bayar`  INT(11)     NOT NULL AUTO_INCREMENT,
  `id_hutang` INT(11)     NOT NULL,
  `tgl`       DATE        NOT NULL,
  `jumlah`    BIGINT      NOT NULL,
  PRIMARY KEY (`id_bayar`),
  KEY `idx_id_hutang` (`id_hutang`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------
-- MODUL 5 — SDM: PENGGAJIAN & LEMBUR
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pegawai` (
  `id_pegawai`   INT(11)     NOT NULL AUTO_INCREMENT,
  `nama`         VARCHAR(60) NOT NULL,
  `jabatan`      VARCHAR(40) NULL,       -- mekanik / kasir / admin
  `wa`           VARCHAR(20) NULL,
  `gaji_pokok`   BIGINT      NOT NULL DEFAULT 0,
  `tarif_lembur` BIGINT      NOT NULL DEFAULT 0,  -- per jam
  `status`       ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_pegawai`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `slip_gaji` (
  `id_slip`      INT(11)     NOT NULL AUTO_INCREMENT,
  `id_pegawai`   INT(11)     NOT NULL,
  `periode`      VARCHAR(20) NOT NULL,   -- mis. '2026-07' atau '01-15 Jul'
  `gaji_pokok`   BIGINT      NOT NULL DEFAULT 0,
  `jam_lembur`   INT(11)     NOT NULL DEFAULT 0,
  `tarif_lembur` BIGINT      NOT NULL DEFAULT 0,
  `bonus_lembur` BIGINT      NOT NULL DEFAULT 0,  -- jam_lembur * tarif_lembur
  `potongan`     BIGINT      NOT NULL DEFAULT 0,
  `gaji_bersih`  BIGINT      NOT NULL DEFAULT 0,  -- pokok + bonus - potongan
  `tgl_bayar`    DATE        NOT NULL,
  PRIMARY KEY (`id_slip`),
  KEY `idx_id_pegawai` (`id_pegawai`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  OPSIONAL (SANGAT DISARANKAN) — KEAMANAN LOGIN
--  Password saat ini tersimpan plaintext. Untuk hash-kan password,
--  lihat docs/ROADMAP_UPGRADE.md bagian "Keamanan". JANGAN jalankan
--  konversi hash sebelum login_auth.php diperbarui, agar tidak terkunci.
-- =====================================================================
