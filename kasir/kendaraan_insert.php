<?php
include("sess_check.php");

$id_kon     = (int)$_POST['id_kon'];
$plat_nomor = mysqli_real_escape_string($conn, strtoupper(trim($_POST['plat_nomor'])));
$merk       = mysqli_real_escape_string($conn, trim($_POST['merk']));
$tipe       = mysqli_real_escape_string($conn, trim($_POST['tipe']));
$tahun      = mysqli_real_escape_string($conn, trim($_POST['tahun']));

if($id_kon == 0 || $plat_nomor === '') {
    echo "<script>alert('Pelanggan dan Plat Nomor wajib diisi.'); window.location='kendaraan_tambah.php';</script>";
    exit;
}

// Cek duplikat plat
$cek = mysqli_query($conn, "SELECT id_kendaraan FROM kendaraan WHERE plat_nomor='$plat_nomor' LIMIT 1");
if(mysqli_num_rows($cek) > 0) {
    echo "<script>alert('Plat nomor sudah terdaftar.'); window.location='kendaraan_tambah.php';</script>";
    exit;
}

$sql = "INSERT INTO kendaraan (id_kon, plat_nomor, merk, tipe, tahun)
        VALUES ('$id_kon', '$plat_nomor', '$merk', '$tipe', '$tahun')";
$res = mysqli_query($conn, $sql);

if($res) {
    echo "<script>alert('Tambah Kendaraan Berhasil!'); window.location='kendaraan.php';</script>";
} else {
    echo "Error: " . mysqli_error($conn);
    echo "<script>alert('Gagal menambah kendaraan.'); window.location='kendaraan_tambah.php';</script>";
}
?>