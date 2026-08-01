<?php
include("sess_check.php");

if(isset($_POST['perbarui'])) {
    $id        = (int)$_POST['id'];
    $id_kon    = (int)$_POST['id_kon'];
    $plat      = mysqli_real_escape_string($conn, strtoupper(trim($_POST['plat_nomor'])));
    $merk      = mysqli_real_escape_string($conn, trim($_POST['merk']));
    $tipe      = mysqli_real_escape_string($conn, trim($_POST['tipe']));
    $tahun     = mysqli_real_escape_string($conn, trim($_POST['tahun']));

    if($id == 0 || $id_kon == 0 || $plat === '') {
        echo "<script>alert('Data tidak lengkap.'); window.location='kendaraan.php';</script>";
        exit;
    }

    // Cek duplikat plat (kecuali untuk kendaraan ini)
    $cek = mysqli_query($conn, "SELECT id_kendaraan FROM kendaraan WHERE plat_nomor='$plat' AND id_kendaraan!='$id' LIMIT 1");
    if(mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Plat nomor sudah terdaftar pada kendaraan lain.'); window.location='kendaraan_edit.php?id=$id';</script>";
        exit;
    }

    $sql = "UPDATE kendaraan SET
            id_kon='$id_kon',
            plat_nomor='$plat',
            merk='$merk',
            tipe='$tipe',
            tahun='$tahun'
            WHERE id_kendaraan='$id'";
    $res = mysqli_query($conn, $sql);

    header("Location: kendaraan.php?act=update&msg=success");
    exit;
}
?>