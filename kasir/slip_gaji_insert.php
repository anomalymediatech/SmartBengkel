<?php
include("sess_check.php");

if(isset($_POST['simpan'])) {
    $id_pegawai   = (int)$_POST['id_pegawai'];
    $periode      = mysqli_real_escape_string($conn, trim($_POST['periode']));
    $gaji_pokok   = (int)$_POST['gaji_pokok'];
    $tarif_lembur = (int)$_POST['tarif_lembur'];
    $jam_lembur   = (int)$_POST['jam_lembur'];
    $bonus_lembur = (int)$_POST['bonus_lembur'];
    $potongan     = (int)$_POST['potongan'];
    $gaji_bersih  = (int)$_POST['gaji_bersih'];
    $tgl_bayar    = $_POST['tgl_bayar'];

    if($id_pegawai == 0 || $periode === '' || $tgl_bayar === '') {
        echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
        exit;
    }

    // Hitung ulang untuk validasi
    $calc_bonus = $jam_lembur * $tarif_lembur;
    $calc_bersih = $gaji_pokok + $calc_bonus - $potongan;
    if($calc_bersih != $gaji_bersih) {
        $gaji_bersih = $calc_bersih;
    }

    $sql = "INSERT INTO slip_gaji (id_pegawai, periode, gaji_pokok, jam_lembur, tarif_lembur, bonus_lembur, potongan, gaji_bersih, tgl_bayar)
            VALUES ($id_pegawai, '$periode', $gaji_pokok, $jam_lembur, $tarif_lembur, $calc_bonus, $potongan, $gaji_bersih, '$tgl_bayar')";
    $res = mysqli_query($conn, $sql);

    if($res) {
        header("Location: slip_gaji.php?msg=".urlencode('Slip gaji berhasil dibuat'));
    } else {
        header("Location: slip_gaji_tambah.php?id_pegawai=$id_pegawai&msg=".urlencode('Error: '.mysqli_error($conn)));
    }
    exit;
}
header("Location: slip_gaji.php");
?>