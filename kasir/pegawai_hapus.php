<?php
include("sess_check.php");

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM pegawai WHERE id_pegawai=$id";
    $res = mysqli_query($conn, $sql);
    header("Location: pegawai.php?msg=".urlencode($res ? 'Pegawai berhasil dihapus' : 'Error: '.mysqli_error($conn)));
    exit;
}
header("Location: pegawai.php");
?>