<?php
/**
 * SmartBengkel - Setup Sample Data Generator
 * Idempotent: aman dijalankan berkali-kali (tidak menduplikasi data).
 * Dipanggil oleh setup.ps1, atau manual: php setup_sample_data.php
 */
require_once 'dist/config/koneksi.php';

function seed_barang($conn) {
    // Cek apakah sudah ada barang non-sampel
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM barangjasa WHERE id_adm=1");
    $row = mysqli_fetch_assoc($res);
    if ($row['c'] > 3) return 0;

    $items = [
        ['Oli Mesin 5W-30', 'barang', 50, 85000, 65000, 10, 'Oli original, aman untuk mesin matic'],
        ['Oli Mesin 10W-40', 'barang', 30, 95000, 72000, 8, 'Semi synthetic'],
        ['Busi Iridium', 'barang', 40, 45000, 32000, 10, 'Umur panjang'],
        ['Filter Oli', 'barang', 25, 25000, 18000, 5, 'OEM'],
        ['Kampas Rem Depan', 'barang', 20, 120000, 85000, 5, 'Set lengkap'],
        ['Kampas Rem Belakang', 'barang', 15, 95000, 70000, 5, 'Set lengkap'],
        ['Aki Kering 12V 5Ah', 'barang', 10, 250000, 180000, 3, 'GS/ASTRA'],
        ['Ban Tubeless 80/100-17', 'barang', 8, 450000, 350000, 2, 'Depan'],
        ['Ban Tubeless 90/90-17', 'barang', 6, 500000, 380000, 2, 'Belakang'],
        ['Kampas Kopling', 'barang', 12, 85000, 60000, 3, 'Set'],
        ['Rantai & Gir Set', 'barang', 7, 280000, 210000, 2, 'Set'],
        ['V-belt Matic', 'barang', 18, 150000, 110000, 4, 'Original'],
        ['Oli Gardan', 'barang', 22, 30000, 20000, 6, '90ml'],
        ['Ganti Oli', 'jasa', 0, 50000, 0, 0, 'Termasuk jasa ganti oli'],
        ['Service Ringan', 'jasa', 0, 150000, 0, 0, 'Cek komplit + setel kopling/rem'],
        ['Service Berat', 'jasa', 0, 350000, 0, 0, 'Ganti oli, busi, filter, setel mesin'],
        ['Ganti Busi', 'jasa', 0, 35000, 0, 0, 'Jasa pasang busi'],
        ['Setel Kopling & Rem', 'jasa', 0, 45000, 0, 0, 'Jasa setel'],
        ['Cuci Karburator', 'jasa', 0, 75000, 0, 0, 'Jasa cuci'],
        ['Ganti Ban', 'jasa', 0, 30000, 0, 0, 'Per ban'],
        ['Cek Mesin (Diagnosa)', 'jasa', 0, 50000, 0, 0, 'Jasa diagnosa'],
    ];

    $n = 0;
    foreach ($items as $i) {
        list($nama, $jenis, $stok, $harga, $modal, $min, $ket) = $i;
        // cek duplikat per nama+jenis
        $cek = mysqli_query($conn, "SELECT id_brg FROM barangjasa WHERE nama='"
            . mysqli_real_escape_string($conn, $nama) . "' AND jenis='$jenis' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) continue;
        $sql = "INSERT INTO barangjasa (nama, jenis, stok, harga, harga_modal, stok_min, keterangan, id_adm)
                VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '$jenis', '$stok', '$harga', '$modal', '$min', '" . mysqli_real_escape_string($conn, $ket) . "', '1')";
        if (mysqli_query($conn, $sql)) $n++;
    }
    return $n;
}

function seed_konsumen($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM konsumen WHERE id_kon != 0");
    $row = mysqli_fetch_assoc($res);
    if ($row['c'] >= 5) return 0;

    $customers = [
        ['Budi Santoso', '081234567890', '081234567890', 'Jl. Merdeka No. 10, Bekasi', 'B 1234 AB', 'Honda', 'Beat', '2020'],
        ['Siti Rahayu', '081345678901', '081345678901', 'Jl. Sudirman No. 25, Bekasi', 'B 5678 CD', 'Yamaha', 'NMAX', '2022'],
        ['Ahmad Fauzi', '081456789012', '081456789012', 'Jl. Ahmad Yani No. 7, Bekasi', 'B 9012 EF', 'Suzuki', 'Nex', '2019'],
        ['Dewi Lestari', '081567890123', '081567890123', 'Jl. Gatot Subroto No. 15, Bekasi', 'B 3456 GH', 'Honda', 'Vario', '2021'],
        ['Rudi Hermawan', '081678901234', '081678901234', 'Jl. Pahlawan No. 3, Bekasi', 'B 7890 IJ', 'Kawasaki', 'Ninja', '2023'],
    ];

    $n = 0;
    foreach ($customers as $c) {
        list($nama, $telp, $wa, $alamat, $plat, $merk, $tipe, $tahun) = $c;
        $cek = mysqli_query($conn, "SELECT id_kon FROM konsumen WHERE nama_kon='" . mysqli_real_escape_string($conn, $nama) . "' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) continue;
        $sql = "INSERT INTO konsumen (nama_kon, telp_kon, wa_kon, alamat_kon, plat_nomor)
                VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '$telp', '$wa', '" . mysqli_real_escape_string($conn, $alamat) . "', '$plat')";
        if (!mysqli_query($conn, $sql)) continue;
        $id_kon = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO kendaraan (id_kon, plat_nomor, merk, tipe, tahun)
            VALUES ('$id_kon', '$plat', '$merk', '$tipe', '$tahun')");
        $n++;
    }
    return $n;
}

function seed_supplier($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM supplier");
    $row = mysqli_fetch_assoc($res);
    if ($row['c'] >= 4) return 0;

    $suppliers = [
        ['PT. Sumber Makmur', 'Jl. Industri No. 5, Jakarta', '021-5550101', '08125550101'],
        ['CV. Berkah Jaya', 'Jl. Raya Bekasi KM 21', '021-8880202', '08138880202'],
        ['UD. Motor Parts', 'Jl. Otomotif No. 12, Bekasi', '021-7770303', '08147770303'],
        ['PT. Anugrah Abadi', 'Kawasan Pergudangan Cikarang', '021-6660404', '08156660404'],
    ];

    $n = 0;
    foreach ($suppliers as $s) {
        list($nama, $alamat, $telp, $wa) = $s;
        $cek = mysqli_query($conn, "SELECT id_spl FROM supplier WHERE nama_spl='" . mysqli_real_escape_string($conn, $nama) . "' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) continue;
        $sql = "INSERT INTO supplier (nama_spl, telp_spl, alamat_spl, wa_spl)
                VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '$telp', '" . mysqli_real_escape_string($conn, $alamat) . "', '$wa')";
        if (mysqli_query($conn, $sql)) $n++;
    }
    return $n;
}

function seed_pegawai($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM pegawai");
    $row = mysqli_fetch_assoc($res);
    if ($row['c'] >= 4) return 0;

    $employees = [
        ['Agus Setiawan', 'Mekanik', '081211112222', 3500000, 25000],
        ['Bambang Widodo', 'Mekanik', '081222223333', 3200000, 25000],
        ['Citra Dewi', 'Kasir', '081233334444', 3000000, 20000],
        ['Dedi Supriadi', 'Admin', '081244445555', 4000000, 30000],
    ];

    $n = 0;
    foreach ($employees as $e) {
        list($nama, $jabatan, $wa, $gaji, $tarif) = $e;
        $cek = mysqli_query($conn, "SELECT id_pegawai FROM pegawai WHERE nama='" . mysqli_real_escape_string($conn, $nama) . "' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) continue;
        $sql = "INSERT INTO pegawai (nama, jabatan, wa, gaji_pokok, tarif_lembur, status)
                VALUES ('" . mysqli_real_escape_string($conn, $nama) . "', '$jabatan', '$wa', '$gaji', '$tarif', 'Aktif')";
        if (mysqli_query($conn, $sql)) $n++;
    }
    return $n;
}

function seed_toko($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM toko");
    $row = mysqli_fetch_assoc($res);
    if ($row['c'] > 0) return 0;
    mysqli_query($conn, "INSERT INTO toko (id_toko, nama_toko, alamat, telp, footer_nota)
        VALUES (1, 'Bengkel Maju Jaya', 'Jl. Raya Bekasi No. 100, Bekasi', '021-8899000', 'Terima kasih atas kunjungan Anda')");
    return 1;
}

function seed_transaksi($conn) {
    // Backfill trx_detail untuk transaksi lama yang belum punya detail.
    // (laporan laba membaca dari trx_detail, jadi transaksi tanpa detail
    //  akan tampil dengan laba 0.)
    $res = mysqli_query($conn, "SELECT t.id_trx FROM trx t
                                WHERE NOT EXISTS (SELECT 1 FROM trx_detail d WHERE d.id_trx = t.id_trx)
                                LIMIT 100");
    $backfilled = 0;
    while($t = mysqli_fetch_assoc($res)) {
        $id_trx = $t['id_trx'];
        $b = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_brg, nama, jenis, harga, harga_modal FROM barangjasa WHERE jenis='barang' ORDER BY id_brg ASC LIMIT 1"));
        if (!$b) continue;
        $jml = 1;
        $sub = $b['harga'] * $jml;
        mysqli_query($conn, "INSERT INTO trx_detail (id_trx, id_brg, nama_snap, jenis_snap, harga_snap, harga_modal_snap, diskon, jml, subtotal)
            VALUES ('$id_trx', '{$b['id_brg']}', '{$b['nama']}', '{$b['jenis']}', '{$b['harga']}', '{$b['harga_modal']}', '0', '$jml', '$sub')");
        $backfilled++;
    }

    // Ambil 1 konsumen non-Umum
    $k = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_kon, plat_nomor FROM konsumen WHERE id_kon != 0 LIMIT 1"));
    if (!$k) return $backfilled;
    $id_kon = $k['id_kon'];
    $plat = $k['plat_nomor'] ?: 'B 0000 AA';

    // Ambil 2 barang & 1 jasa
    $b1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_brg, nama, jenis, harga, harga_modal FROM barangjasa WHERE jenis='barang' ORDER BY id_brg ASC LIMIT 1"));
    $b2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_brg, nama, jenis, harga, harga_modal FROM barangjasa WHERE jenis='barang' AND id_brg != " . (int)$b1['id_brg'] . " ORDER BY id_brg ASC LIMIT 1"));
    $j1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_brg, nama, jenis, harga, harga_modal FROM barangjasa WHERE jenis='jasa' ORDER BY id_brg ASC LIMIT 1"));
    if (!$b1 || !$j1) return $backfilled;

    $today = date('Y-m-d');
    $n = 0;

    // Trx contoh hanya dibuat bila belum ada transaksi hari ini (anti duplikasi saat re-run)
    $cekT = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM trx WHERE tgl_trx = '$today'"));
    if ($cekT['c'] > 0) return $backfilled;

    // Trx 1: Tunai
    $no1 = date('dmYHis') . '1';
    $sub1 = ($b1['harga'] * 2) + $j1['harga'];
    mysqli_query($conn, "INSERT INTO trx (id_trx, id_kon, tgl_trx, total, id_kasir, metode_bayar, bayar, kembali, diskon_nota, status_bayar, jatuh_tempo, plat_nomor, catatan)
        VALUES ('$no1', '$id_kon', '$today', '$sub1', '1', 'Tunai', '$sub1', '0', '0', 'Lunas', NULL, '$plat', 'Servis rutin')");
    mysqli_query($conn, "INSERT INTO trx_detail (id_trx, id_brg, nama_snap, jenis_snap, harga_snap, harga_modal_snap, diskon, jml, subtotal)
        VALUES ('$no1', '{$b1['id_brg']}', '{$b1['nama']}', '{$b1['jenis']}', '{$b1['harga']}', '{$b1['harga_modal']}', '0', '2', '" . ($b1['harga'] * 2) . "')");
    mysqli_query($conn, "INSERT INTO trx_detail (id_trx, id_brg, nama_snap, jenis_snap, harga_snap, harga_modal_snap, diskon, jml, subtotal)
        VALUES ('$no1', '{$j1['id_brg']}', '{$j1['nama']}', '{$j1['jenis']}', '{$j1['harga']}', '{$j1['harga_modal']}', '0', '1', '{$j1['harga']}')");
    $n++;

    // Trx 2: Hutang/Bon (piutang) — jatuh tempo 2 hari lagi → memicu alert
    if ($b2) {
        $no2 = date('dmYHis') . '2';
        $sub2 = $b2['harga'] * 1;
        $jt = date('Y-m-d', strtotime('+2 days'));
        mysqli_query($conn, "INSERT INTO trx (id_trx, id_kon, tgl_trx, total, id_kasir, metode_bayar, bayar, kembali, diskon_nota, status_bayar, jatuh_tempo, plat_nomor, catatan)
            VALUES ('$no2', '$id_kon', '$today', '$sub2', '1', 'Hutang', '0', '0', '0', 'Belum Lunas', '$jt', '$plat', 'Bon pembelian')");
        mysqli_query($conn, "INSERT INTO trx_detail (id_trx, id_brg, nama_snap, jenis_snap, harga_snap, harga_modal_snap, diskon, jml, subtotal)
            VALUES ('$no2', '{$b2['id_brg']}', '{$b2['nama']}', '{$b2['jenis']}', '{$b2['harga']}', '{$b2['harga_modal']}', '0', '1', '{$b2['harga']}')");
        $n++;
    }

    return $backfilled + $n;
}

function seed_hutang($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM hutang_supplier");
    $row = mysqli_fetch_assoc($res);
    if ($row['c'] > 0) return 0;

    $spl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_spl FROM supplier ORDER BY id_spl ASC LIMIT 1"));
    if (!$spl) return 0;
    $jt = date('Y-m-d', strtotime('+5 days'));
    mysqli_query($conn, "INSERT INTO hutang_supplier (id_spl, tgl, keterangan, total, jatuh_tempo, status_bayar)
        VALUES ('{$spl['id_spl']}', '" . date('Y-m-d') . "', 'Pembelian sparepart bulan berjalan', '750000', '$jt', 'Belum Lunas')");
    return 1;
}

function seed_slip_gaji($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) c FROM slip_gaji");
    $row = mysqli_fetch_assoc($res);
    if ($row['c'] > 0) return 0;

    $pg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_pegawai, gaji_pokok, tarif_lembur FROM pegawai ORDER BY id_pegawai ASC LIMIT 1"));
    if (!$pg) return 0;
    $jam = 8;
    $bonus = $jam * $pg['tarif_lembur'];
    $pot = 50000;
    $bersih = $pg['gaji_pokok'] + $bonus - $pot;
    $periode = date('F Y');
    mysqli_query($conn, "INSERT INTO slip_gaji (id_pegawai, periode, gaji_pokok, jam_lembur, tarif_lembur, bonus_lembur, potongan, gaji_bersih, tgl_bayar)
        VALUES ('{$pg['id_pegawai']}', '$periode', '{$pg['gaji_pokok']}', '$jam', '{$pg['tarif_lembur']}', '$bonus', '$pot', '$bersih', '" . date('Y-m-d') . "')");
    return 1;
}

echo "Seeding SmartBengkel sample data...\n";
echo "  barang/jasa: +" . seed_barang($conn) . "\n";
echo "  konsumen+kendaraan: +" . seed_konsumen($conn) . "\n";
echo "  supplier: +" . seed_supplier($conn) . "\n";
echo "  pegawai: +" . seed_pegawai($conn) . "\n";
echo "  toko: +" . seed_toko($conn) . "\n";
echo "  transaksi contoh: +" . seed_transaksi($conn) . "\n";
echo "  hutang supplier: +" . seed_hutang($conn) . "\n";
echo "  slip gaji: +" . seed_slip_gaji($conn) . "\n";
echo "Selesai.\n";
