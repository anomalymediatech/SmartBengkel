<?php
include("sess_check.php");

if(isset($_POST['perbarui'])) {
    $id          = (int)$_POST['id'];
    $nama        = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $jabatan     = mysqli_real_escape_string($conn, trim($_POST['jabatan']));
    $wa          = mysqli_real_escape_string($conn, trim($_POST['wa']));
    $gaji_pokok  = (int)$_POST['gaji_pokok'];
    $tarif_lembur = (int)$_POST['tarif_lembur'];
    $status      = mysqli_real_escape_string($conn, $_POST['status']);

    if($nama === '') {
        header("Location: pegawai_edit.php?id=$id&msg=".urlencode('Nama wajib diisi'));
        exit;
    }

    $sql = "UPDATE pegawai SET
            nama='$nama',
            jabatan='$jabatan',
            wa='$wa',
            gaji_pokok=$gaji_pokok,
            tarif_lembur=$tarif_lembur,
            status='$status'
            WHERE id_pegawai=$id";
    $res = mysqli_query($conn, $sql);

    header("Location: pegawai.php?msg=".urlencode($res ? 'Pegawai berhasil diupdate' : 'Error: '.mysqli_error($conn)));
    exit;
}
header("Location: pegawai.php");
?>