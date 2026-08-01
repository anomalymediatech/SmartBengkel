<?php
include("sess_check.php");

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM kendaraan WHERE id_kendaraan='$id'";
    $res = mysqli_query($conn, $sql);
    header("Location: kendaraan.php?act=delete&msg=success");
    exit;
}
?>