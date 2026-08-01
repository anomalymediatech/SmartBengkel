<?php
include("sess_check.php");

$nama        = mysqli_real_escape_string($conn, trim($_POST['nama']));
$jabatan     = mysqli_real_escape_string($conn, trim($_POST['jabatan']));
$wa          = mysqli_real_escape_string($conn, trim($_POST['wa']));
$gaji_pokok  = (int)$_POST['gaji_pokok'];
$tarif_lembur = (int)$_POST['tarif_lembur'];
$status      = mysqli_real_escape_string($conn, $_POST['status']);

if($nama === '') {
    echo "<script>alert('Nama wajib diisi.'); window.location='pegawai_tambah.php';</script>";
    exit;
}

$sql = "INSERT INTO pegawai (nama, jabatan, wa, gaji_pokok, tarif_lembur, status)
        VALUES ('$nama', '$jabatan', '$wa', $gaji_pokok, $tarif_lembur, '$status')";
$res = mysqli_query($conn, $sql);

if($res) {
    header("Location: pegawai.php?msg=".urlencode('Pegawai berhasil ditambahkan'));
} else {
    header("Location: pegawai_tambah.php?msg=".urlencode('Error: '.mysqli_error($conn)));
}
?>