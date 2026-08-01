<?php
include("sess_check.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");

$stmt_toko = $conn->prepare("SELECT nama_toko, alamat, telp FROM toko WHERE id_toko=1");
$stmt_toko->execute();
$res_toko = $stmt_toko->get_result();
$toko = $res_toko->fetch_assoc();

$sql = "SELECT bj.*
        FROM barangjasa bj
        WHERE bj.jenis='barang'
        AND CAST(bj.stok AS UNSIGNED) <= bj.stok_min
        ORDER BY (bj.stok_min - CAST(bj.stok AS UNSIGNED)) DESC, bj.nama ASC";
$res = mysqli_query($conn, $sql);

$total_biaya = 0;
$data = [];
while($row = mysqli_fetch_array($res)) {
    $stok = (int)$row['stok'];
    $min = (int)$row['stok_min'];
    $kebutuhan = max(0, $min - $stok);
    $biaya = $kebutuhan * (int)$row['harga_modal'];
    $total_biaya += $biaya;
    $row['kebutuhan'] = $kebutuhan;
    $row['biaya'] = $biaya;
    $data[] = $row;
}

$pagedesc = "Laporan Stok Minimum";
$pagetitle = str_replace(" ", "_", $pagedesc);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $pagetitle ?></title>
	<link href="foto/logo.png" rel="icon" type="images/x-icon">
	<link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="dist/css/offline-font.css" rel="stylesheet">
	<link href="dist/css/custom-report.css" rel="stylesheet">
	<link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
</head>
<body>
	<section id="header-kop">
		<div class="container-fluid">
			<table class="table table-borderless">
				<tbody>
					<tr>
						<td class="text-left" width="20%">
							<img src="foto/logo.png" alt="logo-dkm" width="70" />
						</td>
						<td class="text-center" width="60%">
						<b><?php echo htmlspecialchars($toko['nama_toko'] ?? 'Bengkel Mantap Jiwa'); ?></b> <br>
						<?php echo htmlspecialchars($toko['alamat'] ?? ''); ?><br>
						<?php if(!empty($toko['telp'])): ?>Telp: <?php echo htmlspecialchars($toko['telp']); ?><br><?php endif; ?>
						<td class="text-right" width="20%">
						</td>
					</tr>
				</tbody>
			</table>
			<hr class="line-top" />
		</div>
	</section>

	<section id="body-of-report">
		<div class="container-fluid">
			<h5 class="text-center">Laporan Stok Minimum / Reorder Point</h5>
			<br />
			<table class="table table-striped table-bordered table-hover" id="tabel-data">
				<thead>
					<tr>
						<th width="1%">No</th>
						<th>Nama Barang</th>
						<th class="text-center">Stok</th>
						<th class="text-center">Stok Min</th>
						<th class="text-center">Kebutuhan</th>
						<th class="text-right">Harga Modal</th>
						<th class="text-right">Estimasi Biaya</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$i = 1;
						foreach($data as $d) {
							echo '<tr>';
							echo '<td class="text-center">'. $i++ .'</td>';
							echo '<td>'. htmlspecialchars($d['nama']) .'</td>';
							echo '<td class="text-center">'. (int)$d['stok'] .'</td>';
							echo '<td class="text-center">'. (int)$d['stok_min'] .'</td>';
							echo '<td class="text-center">'. $d['kebutuhan'] .'</td>';
							echo '<td class="text-right">'. format_rupiah($d['harga_modal']) .'</td>';
							echo '<td class="text-right">'. format_rupiah($d['biaya']) .'</td>';
							echo '</tr>';
						}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="6" class="text-center">Total Estimasi Biaya Reorder</th>
						<th class="text-right"><?php echo format_rupiah($total_biaya);?></th>
					</tr>
				</tfoot>
			</table>
			<br />
		</div>
	</section>

	<script type="text/javascript">
		$(document).ready(function() {
			window.print();
		});
	</script>
	<script src="libs/jquery/dist/jquery.min.js"></script>
	<script src="libs/bootstrap/dist/js/bootstrap.min.js"></script>
</body>
</html>
