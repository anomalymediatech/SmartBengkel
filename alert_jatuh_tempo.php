<?php
	// Logika untuk menampilkan alert piutang dan hutang jatuh tempo
	// Menggunakan $conn yang sudah tersedia dari sess_check.php

	// --- PIUTANG JATUH TEMPO ---
	$sql_piutang_alert = "SELECT COUNT(*) c FROM trx
		WHERE metode_bayar='Hutang' AND status_bayar='Belum Lunas'
		AND jatuh_tempo IS NOT NULL AND jatuh_tempo <= CURDATE() + INTERVAL 3 DAY";
	$res_piutang_alert = mysqli_query($conn, $sql_piutang_alert);
	$piutang_count = 0;
	if($res_piutang_alert) {
		$r = mysqli_fetch_array($res_piutang_alert);
		$piutang_count = $r['c'];
	}

	// --- HUTANG SUPPLIER JATUH TEMPO ---
	$sql_hutang_alert = "SELECT COUNT(*) c FROM hutang_supplier
		WHERE status_bayar='Belum Lunas'
		AND jatuh_tempo IS NOT NULL AND jatuh_tempo <= CURDATE() + INTERVAL 3 DAY";
	$res_hutang_alert = mysqli_query($conn, $sql_hutang_alert);
	$hutang_count = 0;
	if($res_hutang_alert) {
		$r = mysqli_fetch_array($res_hutang_alert);
		$hutang_count = $r['c'];
	}

	if($piutang_count > 0 || $hutang_count > 0) {
		echo '<div class="alert alert-blink alert-danger">';
		if($piutang_count > 0) {
			echo '⚠️ Ada <strong>'.$piutang_count.'</strong> piutang pelanggan mendekati/lewat jatuh tempo! <a href="piutang_v2.php" class="alert-link">Lihat Detail</a><br>';
		}
		if($hutang_count > 0) {
			echo '⚠️ Ada <strong>'.$hutang_count.'</strong> hutang supplier mendekati/lewat jatuh tempo! <a href="hutang_supplier_v2.php" class="alert-link">Lihat Detail</a>';
		}
		echo '</div>';
	}
?>
