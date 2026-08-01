<!-- Printing -->
	<link rel="stylesheet" href="css/printing.css">

<?php
include("sess_check.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");
if($_GET) {
	$kode = $_GET['code'];
	$sql = "SELECT trx.*, konsumen.nama_kon, kasir.nama_kasir, toko.nama_toko, toko.alamat, toko.telp, toko.footer_nota
			FROM trx
			JOIN konsumen ON trx.id_kon = konsumen.id_kon
			JOIN kasir ON trx.id_kasir = kasir.id_kasir
			JOIN toko ON toko.id_toko = 1
			WHERE trx.id_trx='". $_GET['code'] ."'";
	$query = mysqli_query($conn,$sql);
	$result = mysqli_fetch_array($query);
	
	// Query detail transaksi
	$sqltmp = "SELECT * FROM trx_detail WHERE id_trx='$kode' ORDER BY id_detail ASC";
	$querytmp = mysqli_query($conn,$sqltmp);
	$res_detail = $querytmp;
	
	$header = $result;
}
else {
	echo "Nomor Transaksi Tidak Terbaca";
	exit;
}
?>
<html>
<head>
</head>
<body>
<div id="section-to-print">
	<section id="header-kop">
		<div class="container-fluid">
			<table class="table table-borderless">
				<tbody>
					<tr>
						<td class="text-left" width="20%">
							<img src="foto/logo.png" alt="logo-dkm" width="70" />
						</td>
						<td class="text-center" width="60%">
						<b><?php echo isset($header['nama_toko']) ? $header['nama_toko'] : 'Bengkel Mantap Jiwa'; ?></b> <br>
						<?php echo isset($header['alamat']) ? $header['alamat'] : 'Bekasi'; ?><br>
						Telp: <?php echo isset($header['telp']) ? $header['telp'] : '(021) 192819189'; ?><br>
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
			<h4 class="text-center">Detail Transaksi</h4>
			<br />
<table width="100%">
	<tr>
		<td width="20%"><b>ID. Transaksi</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo $header['id_trx'];?></td>
	</tr>
	<tr>
		<td width="20%"><b>Tanggal</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo format_tanggal($header['tgl_trx']);?></td>
	</tr>
	<tr>
		<td width="20%"><b>Konsumen</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo $result['nama_kon'];?></td>
	</tr>
	<tr>
		<td width="20%"><b>Kasir</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo $header['nama_kasir'];?></td>
	</tr>
	<tr>
		<td width="20%"><b>Metode Bayar</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo isset($header['metode_bayar']) ? $header['metode_bayar'] : '-'; ?></td>
	</tr>
	<tr>
		<td width="20%"><b>Status Bayar</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo isset($header['status_bayar']) ? $header['status_bayar'] : 'Lunas'; ?></td>
	</tr>
	<?php if(isset($header['jatuh_tempo']) && $header['jatuh_tempo']): ?>
	<tr>
		<td width="20%"><b>Jatuh Tempo</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo format_tanggal($header['jatuh_tempo']); ?></td>
	</tr>
	<?php endif; ?>
	<?php if(isset($header['plat_nomor']) && $header['plat_nomor']): ?>
	<tr>
		<td width="20%"><b>Plat Nomor</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo $header['plat_nomor']; ?></td>
	</tr>
	<?php endif; ?>
	<?php if(isset($header['catatan']) && $header['catatan']): ?>
	<tr>
		<td width="20%"><b>Catatan</b></td>
		<td width="2%"><b>:</b></td>
		<td width="78%"><?php echo $header['catatan']; ?></td>
	</tr>
	<?php endif; ?>
</table>
</br>
	<table class="table table-bordered table-keuangan">
				<thead>
					<tr>
						<th width="1%">No</th>
						<th width="10%">Nama Barang/Jasa</th>
						<th width="5%">Jumlah</th>
						<th width="10%">Harga Satuan</th>
						<th width="8%">Diskon</th>
						<th width="10%">Total</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$i=1;
						$grand=0;

						// Try to fetch from trx_detail table (new structure)
						$sqltmp = "SELECT * FROM trx_detail WHERE id_trx='$kode' ORDER BY id_detail ASC";
						$querytmp = mysqli_query($conn,$sqltmp);

						if($querytmp && mysqli_num_rows($querytmp) > 0) {
							// Use new trx_detail table with snapshots
							while($data = mysqli_fetch_array($querytmp)) {
								echo '<tr>';
								echo '<td class="text-center">'. $i .'</td>';
								echo '<td>'. $data['nama_snap'] .'</td>';
								echo '<td class="text-center">'. $data['jml'] .'</td>';
								echo '<td class="text-right">'. format_rupiah($data['harga_snap']) .'</td>';
								echo '<td class="text-right">'. format_rupiah($data['diskon']) .'</td>';
								echo '<td class="text-right">'. format_rupiah($data['subtotal']) .'</td>';
								echo '</tr>';
								$i++;
								$grand+=$data['subtotal'];
							}
						} else {
							// Fallback to old tmp_trx structure (for backward compatibility)
							$sqltmp = "SELECT tmp_trx.*, barangjasa.* FROM tmp_trx, barangjasa WHERE tmp_trx.id_brg=barangjasa.id_brg
									AND tmp_trx.id_trx='$kode' ORDER BY barangjasa.nama ASC";
							$querytmp = mysqli_query($conn,$sqltmp);

							while($data = mysqli_fetch_array($querytmp)) {
								$diskon = isset($data['diskon']) ? $data['diskon'] : 0;
								$ttl = ($data['jml'] * $data['harga']) - ($diskon * $data['jml']);
								echo '<tr>';
								echo '<td class="text-center">'. $i .'</td>';
								echo '<td>'. $data['nama'] .'</td>';
								echo '<td class="text-center">'. $data['jml'] .'</td>';
								echo '<td class="text-right">'. format_rupiah($data['harga']) .'</td>';
								echo '<td class="text-right">'. format_rupiah($diskon) .'</td>';
								echo '<td class="text-right">'. format_rupiah($ttl) .'</td>';
								echo '</tr>';
								$i++;
								$grand+=$ttl;
							}
						}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="5" class="text-center">Total </th>
						<th class="text-right"><?php echo format_rupiah($grand);?></th>
					</tr>
					<?php if(isset($header['diskon_nota']) && $header['diskon_nota'] > 0): ?>
					<tr>
						<th colspan="5" class="text-center">Diskon Nota </th>
						<th class="text-right"><?php echo format_rupiah($header['diskon_nota']);?></th>
					</tr>
					<?php endif; ?>
					<?php
						$total_akhir = $grand - (isset($header['diskon_nota']) ? $header['diskon_nota'] : 0);
						if(isset($header['diskon_nota']) && $header['diskon_nota'] > 0):
					?>
					<tr>
						<th colspan="5" class="text-center">Total Akhir </th>
						<th class="text-right"><?php echo format_rupiah($total_akhir);?></th>
					</tr>
					<?php endif; ?>
					<?php if(isset($header['metode_bayar']) && $header['metode_bayar'] != 'Hutang'): ?>
					<tr>
						<th colspan="5" class="text-center">Jumlah Bayar </th>
						<th class="text-right"><?php echo format_rupiah(isset($header['bayar']) ? $header['bayar'] : $total_akhir);?></th>
					</tr>
					<?php if(isset($header['kembali']) && $header['kembali'] > 0): ?>
					<tr>
						<th colspan="5" class="text-center">Kembalian </th>
						<th class="text-right"><?php echo format_rupiah($header['kembali']);?></th>
					</tr>
					<?php endif; ?>
					<?php endif; ?>
				</tfoot>
	</table>
	<br />
		</div><!-- /.container -->
	</section>
	<div class="modal-footer">
	  <a href="trx_cetak.php?id=<?php echo $kode;?>" target="_blank" class="btn btn-warning">Cetak</a>
	  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	</div>
</div>

</body>
</html>