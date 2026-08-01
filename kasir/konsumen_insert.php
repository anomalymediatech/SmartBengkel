<?php
include("sess_check.php");

$nama   = mysqli_real_escape_string($conn, trim($_POST['nama']));
$telp   = mysqli_real_escape_string($conn, trim($_POST['telp']));
$wa     = mysqli_real_escape_string($conn, trim($_POST['wa']));
$alamat = mysqli_real_escape_string($conn, trim($_POST['alamat']));
$plat   = mysqli_real_escape_string($conn, trim($_POST['plat_nomor']));

$sql = "INSERT INTO konsumen(nama_kon, telp_kon, wa_kon, alamat_kon, plat_nomor)
        VALUES('$nama', '$telp', '$wa', '$alamat', '$plat')";
$ress = mysqli_query($conn, $sql);
if($ress){
    echo "<script>alert('Tambah Konsumen Berhasil!');</script>";
    echo "<script type='text/javascript'> document.location = 'konsumen.php'; </script>";
}else{
    echo("Error description: " . mysqli_error($conn));
    echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
    echo "<script type='text/javascript'> document.location = 'konsumen_tambah.php'; </script>";
}
?>