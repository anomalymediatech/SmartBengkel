<?php
include("sess_check.php");

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM slip_gaji WHERE id_slip=$id";
    $res = mysqli_query($conn, $sql);
    header("Location: slip_gaji.php?msg=".urlencode($res ? 'Slip gaji berhasil dihapus' : 'Error: '.mysqli_error($conn)));
    exit;
}
header("Location: slip_gaji.php");
?>