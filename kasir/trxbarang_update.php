<?php
	include("sess_check.php");
	
	// query database memperbarui data pada database
	if(isset($_POST['perbarui'])) {
		$id=$_POST['id'];
		$brg=$_POST['brg'];
		$brgold=$_POST['brgold'];
		$spl=$_POST['spl'];
		$tgl=$_POST['tgl'];
		$jml=(int)$_POST['jml'];
		$jmlold=(int)$_POST['jmlold'];
		$ket=$_POST['keterangan'];

		// Update data barang masuk
		$sql = "UPDATE trxbarang SET
				id_brg='". $brg ."',
				id_spl='". $spl ."',
				tgl_trxbrg='". $tgl ."',
				jml_brg='". $jml ."',
				ket_trxbrg='". $ket ."'
				WHERE id_trxbrg='". $id ."'";
		$ress = mysqli_query($conn, $sql);

		// Sesuaikan stok: kurangi stok lama, tambah stok baru
		if($brg == $brgold) {
			$selisih = $jml - $jmlold;
			if($selisih != 0) {
				$q = mysqli_query($conn, "SELECT stok FROM barangjasa WHERE id_brg='". $brg ."'");
				$d = mysqli_fetch_array($q);
				$stokbaru = (int)$d['stok'] + $selisih;
				if($stokbaru < 0) $stokbaru = 0;
				mysqli_query($conn, "UPDATE barangjasa SET stok='". $stokbaru ."' WHERE id_brg='". $brg ."'");
			}
		} else {
			// Barang diganti: kembalikan stok barang lama, tambah stok barang baru
			$q1 = mysqli_query($conn, "SELECT stok FROM barangjasa WHERE id_brg='". $brgold ."'");
			$d1 = mysqli_fetch_array($q1);
			mysqli_query($conn, "UPDATE barangjasa SET stok='". (max(0, (int)$d1['stok'] - $jmlold)) ."' WHERE id_brg='". $brgold ."'");

			$q2 = mysqli_query($conn, "SELECT stok FROM barangjasa WHERE id_brg='". $brg ."'");
			$d2 = mysqli_fetch_array($q2);
			mysqli_query($conn, "UPDATE barangjasa SET stok='". ((int)$d2['stok'] + $jml) ."' WHERE id_brg='". $brg ."'");
		}

		header("location: trxbarang.php?act=update&msg=success");
	}
?>
