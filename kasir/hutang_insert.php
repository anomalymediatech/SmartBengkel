<?php
include("sess_check.php");

$supplier = (int)$_POST['id_spl'];
$tgl      = mysqli_real_escape_string($conn, $_POST['tgl']);
$ket      = mysqli_real_escape_string($conn, trim($_POST['keterangan']));
$total    = (int)$_POST['total'];
$jt       = !empty($_POST['jatuh_tempo']) ? "'".mysqli_real_escape_string($conn, $_POST['jatuh_tempo'])."'" : 'NULL';

if($supplier == 0 || $total <= 0) {
    echo "<script>alert('Data tidak lengkap.'); window.location='hutang.php';</script>";
    exit;
}

$sql = "INSERT INTO hutang_supplier (id_spl, tgl, keterangan, total, jatuh_tempo, status_bayar)
        VALUES ($supplier, '$tgl', '$ket', $total, $jt, 'Belum Lunas')";
$res = mysqli_query($conn, $sql);

if($res) {
    header("Location: hutang.php?msg=".urlencode('Hutang supplier berhasil ditambahkan'));
} else {
    header("Location: hutang.php?msg=".urlencode('Error: '.mysqli_error($conn)));
}
exit;
?>