<?php
	include("sess_check.php");

	// ---- helper aman untuk input ----
	function inp_int($v){ return intval($v); }
	function inp_str($conn,$v){ return mysqli_real_escape_string($conn, trim(strip_tags($v))); }

	$nama     = inp_str($conn, $_POST['nama']);
	$telp     = inp_int($_POST['telp']);
	$wa_kon   = inp_str($conn, $_POST['wa_kon']);
	$plat     = inp_str($conn, $_POST['plat_nomor']);
	$alamat   = inp_str($conn, $_POST['alamat']);

	// Validasi input
	if(empty($nama) || empty($telp)){
		echo "<script>alert('Nama dan Telepon wajib diisi.');document.location='konsumen_tambah_v2.php';</script>";
		exit;
	}

	// Insert dengan kolom baru: wa_kon, plat_nomor
	$sql = "INSERT INTO konsumen(nama_kon,telp,alamat,wa_kon,plat_nomor)
			VALUES('$nama','$telp','$alamat','$wa_kon','$plat')";

	$ress = mysqli_query($conn, $sql);
	if($ress){
		echo "<script>alert('Tambah Konsumen Berhasil!');</script>";
		echo "<script type='text/javascript'> document.location = 'konsumen.php'; </script>";
	}else{
		echo("Error description: " . mysqli_error($conn));
		echo "<script>alert('Ops, terjadi kesalahan. Silahkan coba lagi.');</script>";
		echo "<script type='text/javascript'> document.location = 'konsumen_tambah_v2.php'; </script>";
	}
?>
