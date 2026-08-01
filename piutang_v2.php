<?php
	include("sess_check.php");

	$pagedesc = "Piutang Pelanggan";
	$menuparent = "piutang";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	// ---- helper aman untuk input ----
	function inp_int($v){ return intval($v); }
	function inp_str($conn,$v){ return mysqli_real_escape_string($conn, trim(strip_tags($v))); }

	// =================================================================
	// AKSI 1: CATAT PEMBAYARAN PIUTANG (bayar_piutang)
	// =================================================================
	if(isset($_POST['bayar_piutang'])){
		$id_trx   = inp_str($conn, $_POST['id_trx']);
		$jumlah   = inp_int($_POST['jumlah']);
		$id_kasir = $sess_kasirid;
		$tgl      = date('Y-m-d');

		if($jumlah > 0 && !empty($id_trx)){
			// Ambil total & sudah bayar dari trx
			$sql_trx = "SELECT total, bayar FROM trx WHERE id_trx='$id_trx' LIMIT 1";
			$res_trx = mysqli_query($conn, $sql_trx);
			if(mysqli_num_rows($res_trx) > 0){
				$trx = mysqli_fetch_array($res_trx);

				// Hitung sudah dibayar (bayar_piutang)
				$paid = 0;
				$qpaid = mysqli_query($conn, "SELECT SUM(jumlah) as s FROM bayar_piutang WHERE id_trx='$id_trx'");
				if($qpaid){ $rp = mysqli_fetch_array($qpaid); $paid = $rp['s'] ?? 0; }

				$sisa = $trx['total'] - $trx['bayar'] - $paid;
				if($jumlah > $sisa) $jumlah = $sisa;

				if($jumlah > 0){
					mysqli_begin_transaction($conn);
					$r1 = mysqli_query($conn, "INSERT INTO bayar_piutang(id_trx, tgl, jumlah, id_kasir) VALUES('$id_trx','$tgl','$jumlah','$id_kasir')");

					$total_paid = $trx['bayar'] + $paid + $jumlah;
					$status = ($total_paid >= $trx['total']) ? 'Lunas' : 'Belum Lunas';
					$r2 = mysqli_query($conn, "UPDATE trx SET status_bayar='$status' WHERE id_trx='$id_trx'");

					if($r1 && $r2){
						mysqli_commit($conn);
						echo "<script>alert('Pembayaran piutang berhasil dicatat!');document.location='piutang_v2.php';</script>";
					}else{
						mysqli_rollback($conn);
						echo "<script>alert('Gagal mencatat pembayaran.');document.location='piutang_v2.php';</script>";
					}
				}
			}
		}
	}

	// =================================================================
	// QUERY: DAFTAR PIUTANG (transaksi Hutang yang Belum Lunas)
	// =================================================================
	$sql = "SELECT trx.*, konsumen.nama_kon, konsumen.telp_kon, konsumen.wa_kon,
		(SELECT COALESCE(SUM(jumlah),0) FROM bayar_piutang WHERE id_trx=trx.id_trx) as cicilan
		FROM trx
		LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon
		WHERE trx.metode_bayar='Hutang' AND trx.status_bayar='Belum Lunas'
		ORDER BY trx.tgl_trx ASC";
	$result = mysqli_query($conn, $sql);

	// Total keseluruhan piutang
	$total_piutang = 0;
	$rows = array();
	while($r = mysqli_fetch_array($result)){
		$sisa = $r['total'] - $r['bayar'] - $r['cicilan'];
		$rows[] = array_merge($r, ['sisa' => $sisa]);
		$total_piutang += $sisa;
	}
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row"><div class="col-lg-12"><h1 class="page-header">Piutang Pelanggan (Hutang/Bon)</h1></div></div>
    <div class="row"><div class="col-lg-12"><?php include("layout_alert.php"); ?></div></div>

    <!-- ============ RINGKASAN ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-danger">
          <div class="panel-heading">Total Piutang Belum Lunas</div>
          <div class="panel-body text-center" style="font-size:28px;font-weight:bold;color:#d9534f;">
            <?php echo format_rupiah($total_piutang); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ TABEL PIUTANG ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Daftar Piutang Pelanggan</div>
          <div class="panel-body">
            <table class="table table-striped table-bordered table-hover" id="dataTable">
              <thead>
                <tr>
                  <th width="2%">No</th>
                  <th>No. Nota</th>
                  <th>Tanggal</th>
                  <th>Pelanggan</th>
                  <th>Kontak</th>
                  <th class="text-right">Total</th>
                  <th class="text-right">DP/Bayar</th>
                  <th class="text-right">Cicilan</th>
                  <th class="text-right"><strong>Sisa</strong></th>
                  <th>Jatuh Tempo</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($rows) > 0){ $i=1; foreach($rows as $row){
                    $sisa = $row['sisa'];
                    $jatuh = $row['jatuh_tempo'] ? format_tanggal($row['jatuh_tempo']) : '-';
                    $alert_class = '';
                    if($row['jatuh_tempo']){
                        $diff = (strtotime($row['jatuh_tempo']) - strtotime(date('Y-m-d'))) / 86400;
                        if($diff < 0) $alert_class = 'text-danger fw-bold';
                        elseif($diff <= 3) $alert_class = 'text-warning fw-bold';
                    }
                    $wa = $row['wa_kon'] ? $row['wa_kon'] : $row['telp'];
                    $wa_link = !empty($wa) ? "https://wa.me/62" . ltrim($wa, '0') . "?text=" . rawurlencode("Halo " . $row['nama_kon'] . ", piutang Anda No. " . $row['id_trx'] . " sisa " . format_rupiah($sisa)) : '#';
                ?>
                <tr>
                  <td class="text-center"><?php echo $i++; ?></td>
                  <td><strong><?php echo htmlspecialchars($row['id_trx']); ?></strong></td>
                  <td><?php echo format_tanggal($row['tgl_trx']); ?></td>
                  <td><?php echo htmlspecialchars($row['nama_kon']); ?></td>
                  <td>
                    <?php if($wa != '-'): ?>
                      <a href="<?php echo $wa_link; ?>" target="_blank" class="text-success">
                        <i class="fa fa-whatsapp"></i> <?php echo htmlspecialchars($wa); ?>
                      </a>
                    <?php else: echo '-'; endif; ?>
                  </td>
                  <td class="text-right"><?php echo format_rupiah($row['total']); ?></td>
                  <td class="text-right"><?php echo format_rupiah($row['bayar']); ?></td>
                  <td class="text-right"><?php echo format_rupiah($row['cicilan']); ?></td>
                  <td class="text-right text-danger fw-bold"><?php echo format_rupiah($sisa); ?></td>
                  <td class="<?php echo $alert_class; ?>"><?php echo $jatuh; ?></td>
                  <td class="text-center">
                    <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#bayarModal<?php echo $row['id_trx']; ?>">
                      <i class="fa fa-money"></i> Bayar
                    </button>
                    <a href="trx_detail_v2.php?id=<?php echo $row['id_trx']; ?>" class="btn btn-info btn-xs" target="_blank">
                      <i class="fa fa-eye"></i> Detail
                    </a>
                  </td>
                </tr>

                <!-- Modal Bayar -->
                <div class="modal fade" id="bayarModal<?php echo $row['id_trx']; ?>" tabindex="-1" role="dialog">
                  <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                      <form method="POST">
                        <div class="modal-header bg-success text-white">
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          <h4 class="modal-title">Bayar Piutang: <?php echo htmlspecialchars($row['id_trx']); ?></h4>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="id_trx" value="<?php echo htmlspecialchars($row['id_trx']); ?>">
                          <div class="form-group">
                            <label>Pelanggan</label>
                            <p class="form-control-static"><?php echo htmlspecialchars($row['nama_kon']); ?></p>
                          </div>
                          <div class="form-group">
                            <label>Sisa Piutang</label>
                            <p class="form-control-static text-danger fw-bold" style="font-size:18px;"><?php echo format_rupiah($sisa); ?></p>
                          </div>
                          <div class="form-group">
                            <label>Jumlah Pembayaran (Rp)</label>
                            <input type="number" name="jumlah" class="form-control" min="1" max="<?php echo $sisa; ?>" required placeholder="Masukkan jumlah">
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                          <button type="submit" name="bayar_piutang" class="btn btn-success">Catat Pembayaran</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <?php }}else{ ?>
                <tr><td colspan="11" class="text-center text-muted">Tidak ada piutang yang belum lunas.</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ RIWAYAT PEMBAYARAN PIUTANG ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Riwayat Semua Pembayaran Piutang</div>
          <div class="panel-body">
            <?php
              $hist = mysqli_query($conn, "SELECT bp.*, trx.id_trx, konsumen.nama_kon
                FROM bayar_piutang bp
                JOIN trx ON bp.id_trx=trx.id_trx
                LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon
                ORDER BY bp.tgl DESC LIMIT 50");
            ?>
            <table class="table table-striped table-bordered" id="histTable">
              <thead>
                <tr>
                  <th>Tanggal</th><th>No. Nota</th><th>Pelanggan</th>
                  <th class="text-right">Jumlah</th><th>Kasir</th>
                </tr>
              </thead>
              <tbody>
                <?php if(mysqli_num_rows($hist) > 0){
                    while($h = mysqli_fetch_array($hist)){
                        echo '<tr>';
                        echo '<td>'.format_tanggal($h['tgl']).'</td>';
                        echo '<td>'.$h['id_trx'].'</td>';
                        echo '<td>'.htmlspecialchars($h['nama_kon']).'</td>';
                        echo '<td class="text-right">'.format_rupiah($h['jumlah']).'</td>';
                        echo '<td>'.$h['id_kasir'].'</td>';
                        echo '</tr>';
                    }
                }else{
                    echo '<tr><td colspan="5" class="text-center text-muted">Belum ada riwayat pembayaran.</td></tr>';
                } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#dataTable').DataTable({ "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }, "order": [[9, 'asc']], "pageLength": 25 });
  $('#histTable').DataTable({ "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }, "order": [[0, 'desc']], "pageLength": 15 });
});
</script>
<?php include("layout_bottom.php"); ?>