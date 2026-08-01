<?php
	include("sess_check.php");
	
	// query database memperbarui data pada database
	if(isset($_POST['perbarui'])) {
		$id     = (int)$_POST['kon'];
		$nama   = mysqli_real_escape_string($conn, trim($_POST['nama']));
		$telp   = mysqli_real_escape_string($conn, trim($_POST['telp']));
		$wa     = mysqli_real_escape_string($conn, trim($_POST['wa']));
		$alamat = mysqli_real_escape_string($conn, trim($_POST['alamat']));
		$plat   = mysqli_real_escape_string($conn, trim($_POST['plat_nomor']));
		$sql = "UPDATE konsumen SET
				nama_kon='$nama',
				telp_kon='$telp',
				wa_kon='$wa',
				alamat_kon='$alamat',
				plat_nomor='$plat'
				WHERE id_kon='$id'";
		$ress = mysqli_query($conn, $sql);
		header("location: konsumen.php?act=update&msg=success");
	}
?>