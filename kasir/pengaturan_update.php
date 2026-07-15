<?php
	include("sess_check.php");

	// query database memperbarui data pada database
	if(isset($_POST['perbarui'])) {
		$id_pengguna = $_POST['id_pengguna'];
		$password_old = $_POST['password_old'];
		$password_old2 = $_POST['password_old2'];
		$password_new = $_POST['password_new'];
		$password_new2 = $_POST['password_new2'];

		if($password_old == $password_old2) {
			if($password_new == $password_new2) {
				// Perbaikan: Hashing password dan target tabel/kolom yang benar
				$hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
				$sql = "UPDATE kasir SET pass_kasir='". $hashed_password ."' WHERE id_kasir='". mysqli_real_escape_string($conn, $id_pengguna) ."'";
				$ress = mysqli_query($conn, $sql);

				header("location: pengaturan.php?act=update&msg=success");
			}
			else {
				header("location: pengaturan.php?act=update&msg=pwd_err_2");
			}
		}
		else {
			header("location: pengaturan.php?act=update&msg=pwd_err_1");
		}
	}
?>