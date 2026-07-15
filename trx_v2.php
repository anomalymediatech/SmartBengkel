<?php
	include("sess_check.php");
	$pagedesc = "Daftar Transaksi";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	// ---- helper aman untuk input ----
	function inp_int($v){ return intval($v); }
	function inp_str($conn,$v){ return mysqli_real_escape_string($conn, trim(strip_tags($v))); }

	// =================================================================
	// AKSI: FILTER / PENCARIAN TRANSAKSI
	// =================================================================
	$where = "1=1";
	$tgl_dari = "";
	$tgl_sampai = "";
	$metode_filter = "";

	if(isset($_POST['filter'])){
		if(!empty($_POST['tgl_dari'])){
			$tgl_dari = inp_str($conn, $_POST['tgl_dari']);
			$where .= " AND trx.tgl_trx >= '$tgl_dari'";
		}
		if(!empty($_POST['tgl_sampai'])){
			$tgl_sampai = inp_str($conn, $_POST['tgl_sampai']);
			$where .= " AND trx.tgl_trx <= '$tgl_sampai'";
		}
		if(!empty($_POST['metode_bayar']) && $_POST['metode_bayar'] != 'Semua'){
			$metode_filter = inp_str($conn, $_POST['metode_bayar']);
			$where .= " AND trx.metode_bayar = '$metode_filter'";
		}
	}

	// Query daftar transaksi
	$sql = "SELECT trx.*, konsumen.nama_kon
			FROM trx
			LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon
			WHERE $where
			ORDER BY trx.tgl_trx DESC";
	$result = mysqli_query($conn, $sql);
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row"><div class="col-lg-12"><h1 class="page-header">Daftar Transaksi</h1></div></div>
    <div class="row"><div class="col-lg-12"><?php include("layout_alert.php"); ?></div></div>

    <!-- ============ FORM FILTER ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Filter Transaksi</div>
          <div class="panel-body">
            <form method="POST" class="form-inline">
              <div class="form-group">
                <label>Dari Tgl</label>
                <input type="date" name="tgl_dari" class="form-control" value="<?php echo htmlspecialchars($tgl_dari); ?>">
              </div>
              <div class="form-group">
                <label>Sampai Tgl</label>
                <input type="date" name="tgl_sampai" class="form-control" value="<?php echo htmlspecialchars($tgl_sampai); ?>">
              </div>
              <div class="form-group">
                <label>Metode Bayar</label>
                <select name="metode_bayar" class="form-control">
                  <option value="Semua">Semua</option>
                  <option value="Tunai" <?php if($metode_filter=='Tunai') echo 'selected'; ?>>Tunai</option>
                  <option value="Transfer" <?php if($metode_filter=='Transfer') echo 'selected'; ?>>Transfer</option>
                  <option value="Hutang" <?php if($metode_filter=='Hutang') echo 'selected'; ?>>Hutang</option>
                </select>
              </div>
              <button type="submit" name="filter" class="btn btn-primary">Filter</button>
              <a href="trx_v2.php" class="btn btn-default">Reset</a>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ TABEL DAFTAR ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Data Transaksi</div>
          <div class="panel-body">
            <table class="table table-striped table-bordered table-hover" id="dataTable">
              <thead>
                <tr>
                  <th width="2%">No</th>
                  <th>No. Nota</th>
                  <th>Tanggal</th>
                  <th>Konsumen</th>
                  <th class="text-right">Total</th>
                  <th class="text-center">Metode</th>
                  <th class="text-center">Status</th>
                  <th width="15%">Opsi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  if(mysqli_num_rows($result) > 0){
                    $i = 1;
                    while($row = mysqli_fetch_array($result)){
                      $nama_kon = $row['nama_kon'] ? $row['nama_kon'] : 'Umum';
                      $status_label = $row['status_bayar'] == 'Lunas' ? '<span class="label label-success">Lunas</span>' : '<span class="label label-warning">Belum Lunas</span>';
                      $metode_label = '<span class="label label-info">'.$row['metode_bayar'].'</span>';

                      echo '<tr>';
                      echo '<td class="text-center">'.$i.'</td>';
                      echo '<td><strong>' . htmlspecialchars($data['id_trx']) .'</strong></td>';
                      echo '<td>'.format_tanggal($row['tgl_trx']).'</td>';
                      echo '<td>'.$nama_kon.'</td>';
                      echo '<td class="text-right"><strong>'.format_rupiah($row['total']).'</strong></td>';
                      echo '<td class="text-center">'.$metode_label.'</td>';
                      echo '<td class="text-center">'.$status_label.'</td>';
                      echo '<td class="text-center">';
                      echo '<a href="trx_detail_v2.php?id=' . htmlspecialchars($data['id_trx']) .'" class="btn btn-info btn-xs" title="Lihat Detail"><i class="fa fa-eye"></i> Detail</a> ';
                      echo '<a href="struk_thermal.php?id=' . htmlspecialchars($data['id_trx']) .'" class="btn btn-default btn-xs" title="Cetak Struk"><i class="fa fa-print"></i> Cetak</a>';
                      echo '</td>';
                      echo '</tr>';
                      $i++;
                    }
                  }else{
                    echo '<tr><td colspan="8" class="text-center text-muted">Tidak ada data transaksi.</td></tr>';
                  }
                ?>
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
  $('#dataTable').DataTable({
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json"
    },
    "order": [[2, 'desc']],
    "pageLength": 25
  });
});
</script>
<?php include("layout_bottom.php"); ?>