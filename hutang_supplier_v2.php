<?php
	include("sess_check.php");

	$pagedesc = "Hutang Supplier";
	$menuparent = "hutang";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	// ---- helper aman untuk input ----
	function inp_int($v){ return intval($v); }
	function inp_str($conn,$v){ return mysqli_real_escape_string($conn, trim(strip_tags($v))); }

	// =================================================================
	// AKSI 1: CATAT HUTANG BARU
	// =================================================================
	if(isset($_POST['tambah_hutang'])){
		$id_spl     = inp_int($_POST['id_spl']);
		$tgl        = inp_str($conn, $_POST['tgl']);
		$keterangan = inp_str($conn, $_POST['keterangan']);
		$total      = inp_int($_POST['total']);
		$jatuh_tempo= !empty($_POST['jatuh_tempo']) ? inp_str($conn,$_POST['jatuh_tempo']) : NULL;

		if($id_spl > 0 && $total > 0 && !empty($tgl)){
			$jt = is_null($jatuh_tempo) ? "NULL" : "'$jatuh_tempo'";

			$sql = "INSERT INTO hutang_supplier(id_spl, tgl, keterangan, total, jatuh_tempo, status_bayar)
					VALUES('$id_spl','$tgl','$keterangan','$total',$jt,'Belum Lunas')";
			if(mysqli_query($conn, $sql)){
				echo "<script>alert('Hutang supplier berhasil dicatat!');document.location='hutang_supplier_v2.php';</script>";
			}else{
				echo "<script>alert('Gagal mencatat hutang supplier.');document.location='hutang_supplier_v2.php';</script>";
			}
		} else {
			echo "<script>alert('Data belum lengkap atau total hutang tidak valid.');document.location='hutang_supplier_v2.php';</script>";
		}
	}

	// =================================================================
	// AKSI 2: CATAT PEMBAYARAN HUTANG (bayar_hutang)
	// =================================================================
	if(isset($_POST['bayar_hutang_act'])){
		$id_hutang = inp_int($_POST['id_hutang']);
		$jumlah    = inp_int($_POST['jumlah']);
		$tgl       = date('Y-m-d');

		if($jumlah > 0 && $id_hutang > 0){
			// Ambil total & sudah dibayar dari hutang_supplier
			$sql_hutang = "SELECT total FROM hutang_supplier WHERE id_hutang='$id_hutang' LIMIT 1";
			$res_hutang = mysqli_query($conn, $sql_hutang);
			if(mysqli_num_rows($res_hutang) > 0){
				$hutang = mysqli_fetch_array($res_hutang);

				// Hitung sudah dibayar (bayar_hutang)
				$paid = 0;
				$qpaid = mysqli_query($conn, "SELECT SUM(jumlah) as s FROM bayar_hutang WHERE id_hutang='$id_hutang'");
				if($qpaid){ $rp = mysqli_fetch_array($qpaid); $paid = $rp['s'] ?? 0; }

				$sisa = $hutang['total'] - $paid;
				if($jumlah > $sisa) $jumlah = $sisa;

				if($jumlah > 0){
					mysqli_begin_transaction($conn);
					$r1 = mysqli_query($conn, "INSERT INTO bayar_hutang(id_hutang, tgl, jumlah) VALUES('$id_hutang','$tgl','$jumlah')");

					$total_paid = $paid + $jumlah;
					$status = ($total_paid >= $hutang['total']) ? 'Lunas' : 'Belum Lunas';
					$r2 = mysqli_query($conn, "UPDATE hutang_supplier SET status_bayar='$status' WHERE id_hutang='$id_hutang'");

					if($r1 && $r2){
						mysqli_commit($conn);
						echo "<script>alert('Pembayaran hutang berhasil dicatat!');document.location='hutang_supplier_v2.php';</script>";
					}else{
						mysqli_rollback($conn);
						echo "<script>alert('Gagal mencatat pembayaran.');document.location='hutang_supplier_v2.php';</script>";
					}
				}
			}
		}
	}

	// =================================================================
	// QUERY: DAFTAR HUTANG SUPPLIER
	// =================================================================
	$sql = "SELECT hs.*, spl.nama_spl
			FROM hutang_supplier hs
			LEFT JOIN supplier spl ON hs.id_spl=spl.id_spl
			WHERE hs.status_bayar='Belum Lunas'
			ORDER BY hs.tgl ASC";
	$result = mysqli_query($conn, $sql);

	// Total keseluruhan hutang
	$total_hutang = 0;
	$rows = array();
	while($r = mysqli_fetch_array($result)){
		// Hitung sudah dibayar (bayar_hutang)
		$paid_item = 0;
		$qpaid_item = mysqli_query($conn, "SELECT SUM(jumlah) as s FROM bayar_hutang WHERE id_hutang='{$r['id_hutang']}'");
		if($qpaid_item){ $rp_item = mysqli_fetch_array($qpaid_item); $paid_item = $rp_item['s'] ?? 0; }

		$sisa = $r['total'] - $paid_item;
		$rows[] = array_merge($r, ['sisa' => $sisa, 'bayar_awal' => $paid_item]);
		$total_hutang += $sisa;
	}

	// Query daftar supplier untuk form tambah hutang
	$suppliers_q = mysqli_query($conn, "SELECT id_spl, nama_spl FROM supplier ORDER BY nama_spl ASC");
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row"><div class="col-lg-12"><h1 class="page-header">Hutang Supplier</h1></div></div>
    <div class="row"><div class="col-lg-12"><?php include("layout_alert.php"); ?></div></div>

    <!-- ============ FORM TAMBAH HUTANG ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-primary">
          <div class="panel-heading">Catat Hutang Baru</div>
          <div class="panel-body">
            <form method="POST" class="form-horizontal">
              <div class="form-group">
                <label class="control-label col-sm-3">Supplier</label>
                <div class="col-sm-4">
                  <select name="id_spl" class="form-control" required>
                    <option value="">-- Pilih Supplier --</option>
                    <?php while($spl = mysqli_fetch_array($suppliers_q)){
                      echo '<option value="'.$spl['id_spl'].'">'.htmlspecialchars($spl['nama_spl']).'</option>';
                    } ?>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="control-label col-sm-3">Tanggal Hutang</label>
                <div class="col-sm-4">
                  <input type="date" name="tgl" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
              </div>
              <div class="form-group">
                <label class="control-label col-sm-3">Keterangan</label>
                <div class="col-sm-4">
                  <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Pembelian sparepart bulan ini" maxlength="150">
                </div>
              </div>
              <div class="form-group">
                <label class="control-label col-sm-3">Total Hutang (Rp)</label>
                <div class="col-sm-4">
                  <input type="number" name="total" class="form-control" min="1" required>
                </div>
              </div>
              <div class="form-group">
                <label class="control-label col-sm-3">Jatuh Tempo</label>
                <div class="col-sm-4">
                  <input type="date" name="jatuh_tempo" class="form-control">
                </div>
              </div>
              <div class="form-group">
                <div class="col-sm-offset-3 col-sm-4">
                  <button type="submit" name="tambah_hutang" class="btn btn-warning">
                    <i class="fa fa-plus"></i> Catat Hutang
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ RINGKASAN ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-danger">
          <div class="panel-heading">Total Hutang Belum Lunas</div>
          <div class="panel-body text-center" style="font-size:28px;font-weight:bold;color:#d9534f;">
            <?php echo format_rupiah($total_hutang); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ TABEL HUTANG SUPPLIER ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Daftar Hutang Supplier</div>
          <div class="panel-body">
            <table class="table table-striped table-bordered table-hover" id="dataTable">
              <thead>
                <tr>
                  <th width="2%">No</th>
                  <th>Supplier</th>
                  <th>Tanggal Hutang</th>
                  <th>Keterangan</th>
                  <th class="text-right">Total</th>
                  <th class="text-right">Sudah Bayar</th>
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
                ?>
                <tr>
                  <td class="text-center"><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars($row['nama_spl']); ?></td>
                  <td><?php echo format_tanggal($row['tgl']); ?></td>
                  <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                  <td class="text-right"><?php echo format_rupiah($row['total']); ?></td>
                  <td class="text-right"><?php echo format_rupiah($row['bayar_awal']); ?></td>
                  <td class="text-right text-danger fw-bold"><?php echo format_rupiah($sisa); ?></td>
                  <td class="<?php echo $alert_class; ?>"><?php echo $jatuh; ?></td>
                  <td class="text-center">
                    <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#bayarHutangModal<?php echo $row['id_hutang']; ?>">
                      <i class="fa fa-money"></i> Bayar
                    </button>
                  </td>
                </tr>

                <!-- Modal Bayar Hutang -->
                <div class="modal fade" id="bayarHutangModal<?php echo $row['id_hutang']; ?>" tabindex="-1" role="dialog">
                  <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                      <form method="POST">
                        <div class="modal-header bg-success text-white">
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          <h4 class="modal-title">Bayar Hutang: <?php echo htmlspecialchars($row['nama_spl']); ?></h4>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="id_hutang" value="<?php echo htmlspecialchars($row['id_hutang']); ?>">
                          <div class="form-group">
                            <label>Keterangan</label>
                            <p class="form-control-static"><?php echo htmlspecialchars($row['keterangan']); ?></p>
                          </div>
                          <div class="form-group">
                            <label>Sisa Hutang</label>
                            <p class="form-control-static text-danger fw-bold" style="font-size:18px;"><?php echo format_rupiah($sisa); ?></p>
                          </div>
                          <div class="form-group">
                            <label>Jumlah Pembayaran (Rp)</label>
                            <input type="number" name="jumlah" class="form-control" min="1" max="<?php echo $sisa; ?>" required placeholder="Masukkan jumlah">
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                          <button type="submit" name="bayar_hutang_act" class="btn btn-success">Catat Pembayaran</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <?php }}else{ ?>
                <tr><td colspan="9" class="text-center text-muted">Tidak ada hutang yang belum lunas.</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ RIWAYAT PEMBAYARAN HUTANG ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Riwayat Semua Pembayaran Hutang</div>
          <div class="panel-body">
            <?php
              $hist = mysqli_query($conn, "SELECT bh.*, hs.keterangan, spl.nama_spl
                FROM bayar_hutang bh
                JOIN hutang_supplier hs ON bh.id_hutang=hs.id_hutang
                LEFT JOIN supplier spl ON hs.id_spl=spl.id_spl
                ORDER BY bh.tgl DESC LIMIT 50");
            ?>
            <table class="table table-striped table-bordered" id="histTable">
              <thead>
                <tr>
                  <th>Tanggal</th><th>Supplier</th><th>Keterangan Hutang</th>
                  <th class="text-right">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <?php if(mysqli_num_rows($hist) > 0){
                    while($h = mysqli_fetch_array($hist)){
                        echo '<tr>';
                        echo '<td>'.format_tanggal($h['tgl']).'</td>';
                        echo '<td>'.htmlspecialchars($h['nama_spl']).'</td>';
                        echo '<td>'.htmlspecialchars($h['keterangan']).'</td>';
                        echo '<td class="text-right">'.format_rupiah($h['jumlah']).'</td>';
                        echo '</tr>';
                    }
                }else{
                    echo '<tr><td colspan="4" class="text-center text-muted">Belum ada riwayat pembayaran hutang.</td></tr>';
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
  $('#dataTable').DataTable({ "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }, "order": [[7, 'asc']], "pageLength": 25 });
  $('#histTable').DataTable({ "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }, "order": [[0, 'desc']], "pageLength": 15 });
});
</script>
<?php include("layout_bottom.php"); ?>