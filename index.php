<?php
	// Task 4.1 — Auth guard + format helper (harus baris pertama)
	include("sess_check.php");
	include("dist/function/format_rupiah.php");

	// ---------------------------------------------------------------
	// Task 4.1 — Query sinkron: Omset + Jumlah Transaksi hari ini
	// ---------------------------------------------------------------
	$sql_stats = "SELECT COUNT(id_trx) AS jml_trx, COALESCE(SUM(total),0) AS omset
	              FROM trx WHERE tgl_trx = CURDATE() AND status_bayar != 'Batal'";
	$res_stats  = mysqli_query($conn, $sql_stats);
	$jml_trx    = 0;
	$omset      = 0;
	if ($res_stats) {
		$row_stats = mysqli_fetch_assoc($res_stats);
		$jml_trx   = (int)$row_stats['jml_trx'];
		$omset     = (float)$row_stats['omset'];
	}

	// ---------------------------------------------------------------
	// Task 4.1 — Query sinkron: Item Terjual hari ini
	// ---------------------------------------------------------------
	$sql_items    = "SELECT COALESCE(SUM(td.jml),0) AS item_terjual
	                 FROM trx_detail td JOIN trx t ON td.id_trx = t.id_trx
	                 WHERE t.tgl_trx = CURDATE() AND t.status_bayar != 'Batal'";
	$res_items    = mysqli_query($conn, $sql_items);
	$item_terjual = 0;
	if ($res_items) {
		$row_items    = mysqli_fetch_assoc($res_items);
		$item_terjual = (int)$row_items['item_terjual'];
	}

	// ---------------------------------------------------------------
	// Task 4.1 — Query sinkron: Pelanggan Unik hari ini
	// ---------------------------------------------------------------
	$sql_pelanggan   = "SELECT COUNT(DISTINCT id_kon) AS pelanggan_unik
	                    FROM trx WHERE tgl_trx = CURDATE()
	                    AND id_kon != 0 AND status_bayar != 'Batal'";
	$res_pelanggan   = mysqli_query($conn, $sql_pelanggan);
	$pelanggan_unik  = 0;
	if ($res_pelanggan) {
		$row_pelanggan  = mysqli_fetch_assoc($res_pelanggan);
		$pelanggan_unik = (int)$row_pelanggan['pelanggan_unik'];
	}

	// ---------------------------------------------------------------
	// Task 4.1 — Query sinkron: Laba Kotor hari ini
	// Pakai @$ karena kolom harga_modal_snap mungkin belum ada
	// ---------------------------------------------------------------
	$sql_laba = "SELECT COALESCE(SUM(td.subtotal),0) - COALESCE(SUM(td.harga_modal_snap * td.jml),0) AS laba_kotor
	             FROM trx_detail td JOIN trx t ON td.id_trx = t.id_trx
	             WHERE t.tgl_trx = CURDATE() AND t.status_bayar != 'Batal'";
	@$res_laba = mysqli_query($conn, $sql_laba);
	$laba_kotor = 0;
	if ($res_laba) {
		$row_laba   = mysqli_fetch_assoc($res_laba);
		$laba_kotor = (float)$row_laba['laba_kotor'];
	}

	// ---------------------------------------------------------------
	// Task 4.5 — Query sinkron: Stok Menipis (maks 5)
	// Pakai @$ karena kolom stok_min mungkin belum ada
	// ---------------------------------------------------------------
	$sql_stok   = "SELECT nama, stok, stok_min FROM barangjasa
	               WHERE jenis='barang' AND CAST(stok AS UNSIGNED) <= stok_min
	               ORDER BY CAST(stok AS UNSIGNED) ASC LIMIT 5";
	@$res_stok  = mysqli_query($conn, $sql_stok);
	$show_stok  = ($res_stok !== false);

	// ---------------------------------------------------------------
	// Set page description dan muat layout header
	// ---------------------------------------------------------------
	$pagedesc = "Beranda";
	include("layout_top.php");
?>
<!-- /#page-wrapper -->
<div id="page-wrapper">
	<div class="container-fluid">

		<!-- Task 4.5 — Alert Jatuh Tempo (file sudah ada, tidak dimodifikasi) -->
		<?php include("alert_jatuh_tempo.php"); ?>

		<!-- =========================================================
		     Task 4.3 — BARIS 1: 4 Widget Statistik (col-lg-3 col-md-6)
		     ========================================================= -->
		<div class="row">

			<!-- Widget 1: Omset Hari Ini -->
			<div class="col-lg-3 col-md-6">
				<div class="panel panel-primary">
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<i class="fa fa-money fa-3x"></i>
							</div>
							<div class="col-xs-9 text-right">
								<div class="huge"><?php echo format_rupiah($omset); ?></div>
								<div>Omset Hari Ini</div>
							</div>
						</div>
					</div>
					<a href="trx.php">
						<div class="panel-footer">
							<span class="pull-left">Lihat Rincian</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
				</div>
			</div><!-- /.col -->

			<!-- Widget 2: Jumlah Transaksi -->
			<div class="col-lg-3 col-md-6">
				<div class="panel panel-yellow">
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<i class="fa fa-shopping-cart fa-3x"></i>
							</div>
							<div class="col-xs-9 text-right">
								<div class="huge"><?php echo $jml_trx; ?></div>
								<div>Jumlah Transaksi</div>
							</div>
						</div>
					</div>
					<a href="trx.php">
						<div class="panel-footer">
							<span class="pull-left">Lihat Rincian</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
				</div>
			</div><!-- /.col -->

			<!-- Widget 3: Item Terjual -->
			<div class="col-lg-3 col-md-6">
				<div class="panel panel-green">
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<i class="fa fa-tags fa-3x"></i>
							</div>
							<div class="col-xs-9 text-right">
								<div class="huge"><?php echo $item_terjual; ?></div>
								<div>Item Terjual</div>
							</div>
						</div>
					</div>
					<a href="trx.php">
						<div class="panel-footer">
							<span class="pull-left">Lihat Rincian</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
				</div>
			</div><!-- /.col -->

			<!-- Widget 4: Pelanggan Unik -->
			<div class="col-lg-3 col-md-6">
				<div class="panel panel-teal">
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<i class="fa fa-users fa-3x"></i>
							</div>
							<div class="col-xs-9 text-right">
								<div class="huge"><?php echo $pelanggan_unik; ?></div>
								<div>Pelanggan</div>
							</div>
						</div>
					</div>
					<a href="konsumen.php">
						<div class="panel-footer">
							<span class="pull-left">Lihat Rincian</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
				</div>
			</div><!-- /.col -->

		</div><!-- /.row baris 1 -->

		<!-- =========================================================
		     Task 4.3 & 4.5 — BARIS 2: Laba Kotor + Panel Stok Menipis
		     ========================================================= -->
		<div class="row">

			<!-- Task 4.3 — Widget Laba Kotor -->
			<div class="col-lg-4 col-md-6">
				<div class="panel <?php echo ($laba_kotor > 0) ? 'panel-green' : 'panel-red'; ?>">
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<i class="fa fa-line-chart fa-3x"></i>
							</div>
							<div class="col-xs-9 text-right">
								<div class="huge"><?php echo format_rupiah($laba_kotor); ?></div>
								<div>Laba Kotor Hari Ini</div>
							</div>
						</div>
					</div>
					<a href="laporan.php">
						<div class="panel-footer">
							<span class="pull-left">Lihat Laporan</span>
							<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
							<div class="clearfix"></div>
						</div>
					</a>
				</div>
			</div><!-- /.col laba kotor -->

			<!-- Task 4.5 — Panel Stok Menipis -->
			<?php if ($show_stok): ?>
			<div class="col-lg-8 col-md-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fa fa-exclamation-triangle fa-fw"></i> Stok Menipis
					</div>
					<div class="panel-body">
						<?php
						$stok_rows = [];
						while ($row = mysqli_fetch_assoc($res_stok)) {
							$stok_rows[] = $row;
						}
						if (count($stok_rows) === 0):
						?>
						<p class="text-muted text-center">Tidak ada barang dengan stok menipis.</p>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-bordered table-hover table-condensed">
								<thead>
									<tr>
										<th>Nama Barang</th>
										<th class="text-center">Stok</th>
										<th class="text-center">Min</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($stok_rows as $stok_row): ?>
									<tr>
										<td><?php echo htmlspecialchars($stok_row['nama']); ?></td>
										<td class="text-center text-danger"><strong><?php echo htmlspecialchars($stok_row['stok']); ?></strong></td>
										<td class="text-center"><?php echo htmlspecialchars($stok_row['stok_min']); ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php endif; ?>
					</div>
					<div class="panel-footer text-right">
						<a href="barang.php">Lihat Semua <i class="fa fa-arrow-circle-right"></i></a>
					</div>
				</div>
			</div><!-- /.col stok menipis -->
			<?php endif; ?>

		</div><!-- /.row baris 2 -->

		<!-- =========================================================
		     Task 4.8 — BARIS 3: Panel Grafik Penjualan Bulan Ini
		     ========================================================= -->
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fa fa-bar-chart fa-fw"></i> Grafik Penjualan Bulan Ini
					</div>
					<div class="panel-body">
						<div id="chart-container">
							<canvas id="salesChart" height="80"></canvas>
						</div>
					</div>
				</div>
			</div>
		</div><!-- /.row baris 3 -->

	</div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<!-- Task 4.8 — Chart.js CDN + inisialisasi via AJAX -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function(){
	$.getJSON("dashboard_data.php")
	.done(function(json){
		var ctx = document.getElementById('salesChart').getContext('2d');
		new Chart(ctx, {
			type: 'line',
			data: {
				labels: json.labels,
				datasets: [{
					label: 'Omset (Rp)',
					data: json.data,
					borderColor: '#337ab7',
					backgroundColor: 'rgba(51,122,183,0.1)',
					fill: true,
					tension: 0.3
				}]
			},
			options: {
				responsive: true,
				scales: { y: { beginAtZero: true } }
			}
		});
	})
	.fail(function(){
		$('#chart-container').html('<p class="text-danger text-center"><i class="fa fa-exclamation-triangle"></i> Data grafik tidak dapat dimuat.</p>');
	});
});
</script>

<?php
	include("layout_bottom.php");
?>
