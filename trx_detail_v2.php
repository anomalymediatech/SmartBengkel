<?php
	include("sess_check.php");
	$pagedesc = "Detail Transaksi";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	// ---- helper aman untuk input ----
	function inp_int($v){ return intval($v); }
	function inp_str($conn,$v){ return mysqli_real_escape_string($conn, trim(strip_tags($v))); }

	// =================================================================
	// AMBIL ID TRANSAKSI DARI URL
	// =================================================================
	if(!isset($_GET['id']) || empty($_GET['id'])){
		echo "<script>alert('No transaksi tidak ditemukan.');document.location='trx_v2.php';</script>";
		exit;
	}

	$id_trx = inp_str($conn, $_GET['id']);

	// =================================================================
	// QUERY HEADER TRANSAKSI
	// =================================================================
	$sql_header = "SELECT trx.*, konsumen.nama_kon, admin.user_adm
					FROM trx
					LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon
					LEFT JOIN admin ON trx.id_kasir=admin.id_adm
					WHERE trx.id_trx='$id_trx'
					LIMIT 1";
	$res_header = mysqli_query($conn, $sql_header);

	if(mysqli_num_rows($res_header) == 0){
		echo "<script>alert('Transaksi tidak ditemukan.');document.location='trx_v2.php';</script>";
		exit;
	}

	$header = mysqli_fetch_array($res_header);
	$nama_kon = $header['nama_kon'] ? $header['nama_kon'] : 'Umum';
	$user_kasir = $header['user_adm'] ? $header['user_adm'] : '-';

	// =================================================================
	// QUERY DETAIL TRANSAKSI (dari tabel trx_detail)
	// =================================================================
	$sql_detail = "SELECT * FROM trx_detail WHERE id_trx='$id_trx' ORDER BY id_detail ASC";
	$res_detail = mysqli_query($conn, $sql_detail);
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row"><div class="col-lg-12"><h1 class="page-header">Detail Transaksi</h1></div></div>
    <div class="row"><div class="col-lg-12"><?php include("layout_alert.php"); ?></div></div>

    <!-- ============ HEADER TRANSAKSI ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-primary">
          <div class="panel-heading">
            Informasi Transaksi #<?php echo htmlspecialchars($id_trx); ?>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-6">
                <table class="table table-condensed">
                  <tr>
                    <td><strong>No. Nota</strong></td>
                    <td>: <?php echo htmlspecialchars($header['id_trx']); ?></td>
                  </tr>
                  <tr>
                    <td><strong>Tanggal</strong></td>
                    <td>: <?php echo format_tanggal($header['tgl_trx']); ?></td>
                  </tr>
                  <tr>
                    <td><strong>Konsumen</strong></td>
                    <td>: <?php echo htmlspecialchars($nama_kon); ?></td>
                  </tr>
                  <tr>
                    <td><strong>Plat Nomor</strong></td>
                    <td>: <?php echo $header['plat_nomor'] ? htmlspecialchars($header['plat_nomor']) : '-'; ?></td>
                  </tr>
                  <tr>
                    <td><strong>Kasir</strong></td>
                    <td>: <?php echo htmlspecialchars($user_kasir); ?></td>
                  </tr>
                </table>
              </div>
              <div class="col-sm-6">
                <table class="table table-condensed">
                  <tr>
                    <td><strong>Metode Bayar</strong></td>
                    <td>: <span class="label label-info"><?php echo htmlspecialchars($header['metode_bayar']); ?></span></td>
                  </tr>
                  <tr>
                    <td><strong>Status Pembayaran</strong></td>
                    <td>:
                      <?php
                        if($header['status_bayar'] == 'Lunas'){
                          echo '<span class="label label-success">Lunas</span>';
                        }else{
                          echo '<span class="label label-warning">Belum Lunas</span>';
                        }
                      ?>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Jatuh Tempo</strong></td>
                    <td>: <?php echo $header['jatuh_tempo'] ? format_tanggal($header['jatuh_tempo']) : '-'; ?></td>
                  </tr>
                  <tr>
                    <td><strong>Catatan</strong></td>
                    <td>: <?php echo $header['catatan'] ? htmlspecialchars($header['catatan']) : '-'; ?></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ TABEL DETAIL ITEM ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Item Transaksi</div>
          <div class="panel-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th width="1%">No</th>
                  <th>Item</th>
                  <th class="text-center">Jenis</th>
                  <th class="text-right">Harga Satuan</th>
                  <th class="text-right">Diskon/Unit</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  if(mysqli_num_rows($res_detail) > 0){
                    $i = 1;
                    $grand_total = 0;
                    while($detail = mysqli_fetch_array($res_detail)){
                      $grand_total += $detail['subtotal'];
                      echo '<tr>';
                      echo '<td class="text-center">'.$i.'</td>';
                      echo '<td>'.htmlspecialchars($detail['nama_snap']).'</td>';
                      echo '<td class="text-center"><span class="label label-default">'.htmlspecialchars($detail['jenis_snap']).'</span></td>';
                      echo '<td class="text-right">'.format_rupiah($detail['harga_snap']).'</td>';
                      echo '<td class="text-right">'.format_rupiah($detail['diskon']).'</td>';
                      echo '<td class="text-center">'.$detail['jml'].'</td>';
                      echo '<td class="text-right"><strong>'.format_rupiah($detail['subtotal']).'</strong></td>';
                      echo '</tr>';
                      $i++;
                    }
                  }else{
                    echo '<tr><td colspan="7" class="text-center text-muted">Tidak ada item dalam transaksi ini.</td></tr>';
                  }
                ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="6" class="text-right">Subtotal Item</th>
                  <th class="text-right"><strong><?php echo format_rupiah($grand_total); ?></strong></th>
                </tr>
                <tr>
                  <th colspan="6" class="text-right">Diskon Nota</th>
                  <th class="text-right"><strong><?php echo format_rupiah($header['diskon_nota']); ?></strong></th>
                </tr>
                <tr style="background:#f5f5f5;font-size:16px;">
                  <th colspan="6" class="text-right">TOTAL AKHIR</th>
                  <th class="text-right" style="font-size:18px;color:#d9534f;"><strong><?php echo format_rupiah($header['total']); ?></strong></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ PEMBAYARAN ============ -->
    <div class="row">
      <div class="col-lg-6">
        <div class="panel panel-info">
          <div class="panel-heading">Ringkas Pembayaran</div>
          <div class="panel-body">
            <table class="table table-condensed">
              <tr>
                <td><strong>Total Tagihan</strong></td>
                <td class="text-right">: <?php echo format_rupiah($header['total']); ?></td>
              </tr>
              <tr>
                <td><strong>Uang Diterima</strong></td>
                <td class="text-right">: <?php echo format_rupiah($header['bayar']); ?></td>
              </tr>
              <tr style="background:#e8f4f8;">
                <td><strong>Kembalian</strong></td>
                <td class="text-right">: <?php echo format_rupiah($header['kembali']); ?></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="panel panel-default">
          <div class="panel-heading">Aksi</div>
          <div class="panel-body">
            <a href="struk_thermal.php?id=<?php echo htmlspecialchars($id_trx); ?>" class="btn btn-primary btn-block" target="_blank">
              <i class="fa fa-print"></i> Cetak Struk Thermal
            </a>
            <a href="kirim_wa.php?id=<?php echo htmlspecialchars($id_trx); ?>" class="btn btn-success btn-block">
              <i class="fa fa-whatsapp"></i> Kirim ke WhatsApp
            </a>
            <a href="trx_v2.php" class="btn btn-default btn-block">
              <i class="fa fa-arrow-left"></i> Kembali ke Daftar
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include("layout_bottom.php"); ?>
