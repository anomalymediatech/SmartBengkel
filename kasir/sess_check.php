<?php
	// memulai session
	session_start();
	// membaca nilai variabel session 
	$chk_sess = isset($_SESSION['kasir']) ? (int)$_SESSION['kasir'] : 0;
	// memanggil file koneksi
	include("dist/config/koneksi.php");
	include("dist/config/library.php");
	// mengambil data pengguna dari tabel pengguna
	$sql_sess = "SELECT * FROM kasir WHERE id_kasir='$chk_sess'";
	$ress_sess = mysqli_query($conn, $sql_sess);
	$row_sess = mysqli_fetch_array($ress_sess);
	// menyimpan id_pengguna yang sedang login
	$sess_kasirid = $row_sess['id_kasir'];
	$sess_kasiruser = $row_sess['user_kasir'];
	$sess_kasirname = $row_sess['nama_kasir'];
	$sess_kasirfoto = $row_sess['foto_kasir'];
	// mengarahkan ke halaman login.php apabila session belum terdaftar
	if(! isset($_SESSION['kasir'])) {
		header("location: ../login.php?login=false");
	}
?>