<?php
include("sess_check.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");

$stmt_toko = $conn->prepare("SELECT nama_toko, alamat, telp FROM toko WHERE id_toko=1");
$stmt_toko->execute();
$res_toko = $stmt_toko->get_result();
$toko = $res_toko->fetch_assoc();

$tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'semua';

$where = "tgl_trx >= '$tgl_dari' AND tgl_trx <= '$tgl_sampai' AND status_bayar != 'Batal'";
$sql = "SELECT
            t.id_trx,
            t.tgl_trx,
            t.total,
            COALESCE(SUM(td.subtotal),0) as subtotal_jual,
            COALESCE(SUM(td.harga_modal_snap * td.jml),0) as modal_total,
            (COALESCE(SUM(td.subtotal),0) - COALESCE(SUM(td.harga_modal_snap * td.jml),0)) as laba_kotor,
            t.metode_bayar,
            k.nama_kon
        FROM trx t
        LEFT JOIN trx_detail td ON t.id_trx = td.id_trx
        LEFT JOIN konsumen k ON t.id_kon = k.id_kon
        WHERE $where
        GROUP BY t.id_trx
        ORDER BY t.tgl_trx DESC, t.id_trx DESC";
$res = mysqli_query($conn, $sql);

$total_jual = 0;
$total_modal = 0;
$total_laba = 0;
$data = [];
while($row = mysqli_fetch_array($res)) {
    $data[] = $row;
    $total_jual += $row['subtotal_jual'];
    $total_modal += $row['modal_total'];
    $total_laba += $row['laba_kotor'];
}

$pagedesc = "Laporan Laba Kotor - " . IndonesiaTgl($tgl_dari) ." - ". IndonesiaTgl($tgl_sampai);
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
			<h5 class="text-center">Laporan Laba Kotor Periode <?php echo format_tanggal($tgl_dari);?> s/d <?php echo format_tanggal($tgl_sampai);?></h5>
			<br />
			<table class="table table-striped table-bordered table-hover" id="tabel-data">
				<thead>
					<tr>
						<th width="1%">No</th>
						<th width="14%">ID Trx</th>
						<th width="12%">Tgl Trx</th>
						<th width="18%">Pelanggan</th>
						<th width="14%">Total Jual</th>
						<th width="14%">Modal</th>
						<th width="14%">Laba</th>
						<th width="13%">Metode</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$i = 1;
						foreach($data as $d) {
							echo '<tr>';
							echo '<td class="text-center">'. $i++ .'</td>';
							echo '<td class="text-center">'. htmlspecialchars($d['id_trx']) .'</td>';
							echo '<td class="text-center">'. format_tanggal($d['tgl_trx']) .'</td>';
							echo '<td class="text-center">'. htmlspecialchars($d['nama_kon'] ?: 'Umum') .'</td>';
							echo '<td class="text-right">'. format_rupiah($d['subtotal_jual']) .'</td>';
							echo '<td class="text-right">'. format_rupiah($d['modal_total']) .'</td>';
							echo '<td class="text-right">'. format_rupiah($d['laba_kotor']) .'</td>';
							echo '<td class="text-center">'. $d['metode_bayar'] .'</td>';
							echo '</tr>';
						}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="4" class="text-center">Total</th>
						<th class="text-right"><?php echo format_rupiah($total_jual);?></th>
						<th class="text-right"><?php echo format_rupiah($total_modal);?></th>
						<th class="text-right"><?php echo format_rupiah($total_laba);?></th>
						<th></th>
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
