<?php
	include("sess_check.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	$kode = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
	if($kode == ''){ echo "Nomor transaksi kosong."; exit; }

	// header transaksi + konsumen + kasir + toko
	$h = mysqli_query($conn, "SELECT trx.*, konsumen.nama_kon, konsumen.wa_kon, konsumen.telp_kon,
			kasir.nama_kasir FROM trx
			JOIN kasir ON trx.id_kasir=kasir.id_kasir
			LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon
			WHERE trx.id_trx='$kode' LIMIT 1");
	if(mysqli_num_rows($h)==0){ echo "Transaksi tidak ditemukan."; exit; }
	$t = mysqli_fetch_array($h);

	$toko_q = mysqli_query($conn,"SELECT * FROM toko WHERE id_toko=1 LIMIT 1");
	$toko = mysqli_num_rows($toko_q) ? mysqli_fetch_array($toko_q)
	        : array('nama_toko'=>'Bengkel','alamat'=>'','telp'=>'','footer_nota'=>'Terima kasih');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Struk <?php echo $kode;?></title>
<style>
  body{ background:#e9e9e9; font-family:Arial,sans-serif; }
  .struk{
    width:58mm; background:#fff; margin:16px auto; padding:6px 8px;
    font-family:'Courier New',monospace; font-size:11px; color:#000; line-height:1.35;
  }
  .c{ text-align:center; } .r{ text-align:right; } .b{ font-weight:bold; }
  .struk hr{ border:none; border-top:1px dashed #000; margin:4px 0; }
  .struk table{ width:100%; border-collapse:collapse; }
  .struk td{ vertical-align:top; padding:0; }
  .item td{ font-size:11px; }
  .toolbar{ width:58mm; margin:8px auto; text-align:center; }
  .toolbar a,.toolbar button{
    display:inline-block; margin:3px; padding:8px 12px; font-size:12px;
    border:none; border-radius:4px; cursor:pointer; text-decoration:none; color:#fff;
  }
  .btn-print{ background:#337ab7; } .btn-wa{ background:#25D366; } .btn-new{ background:#5cb85c; }
  @media print{
    body{ background:#fff; }
    .toolbar{ display:none; }
    .struk{ margin:0; width:58mm; }
    @page{ margin:0; size:58mm auto; }
  }
</style>
</head>
<body>
<div class="struk" id="struk">
  <div class="c b" style="font-size:13px"><?php echo strtoupper(htmlspecialchars($toko['nama_toko']));?></div>
  <div class="c"><?php echo htmlspecialchars($toko['alamat']);?></div>
  <div class="c">Telp: <?php echo htmlspecialchars($toko['telp']);?></div>
  <hr>
  <table>
    <tr><td>No</td><td class="r"><?php echo $t['id_trx'];?></td></tr>
    <tr><td>Tgl</td><td class="r"><?php echo format_tanggal($t['tgl_trx']);?></td></tr>
    <tr><td>Kasir</td><td class="r"><?php echo $t['nama_kasir'];?></td></tr>
    <tr><td>Plgn</td><td class="r"><?php echo $t['nama_kon'] ? htmlspecialchars($t['nama_kon']) : 'Umum';?></td></tr>
    <?php if($t['plat_nomor']){ ?><tr><td>Plat</td><td class="r"><?php echo htmlspecialchars($t['plat_nomor']);?></td></tr><?php } ?>
  </table>
  <hr>
  <?php
    $d = mysqli_query($conn,"SELECT * FROM trx_detail WHERE id_trx='$kode'");
    $tot_disk_item = 0;
    while($it = mysqli_fetch_array($d)){
      $sub = $it['subtotal'];
      $tot_disk_item += ($it['diskon'] * $it['jml']);
      echo '<table class="item"><tr><td colspan="2">'.htmlspecialchars($it['nama_snap']).'</td></tr>';
      echo '<tr><td>'.$it['jml'].' x '.format_rupiah($it['harga_snap']);
      if($it['diskon']>0) echo ' (-'.format_rupiah($it['diskon']).')';
      echo '</td><td class="r">'.format_rupiah($sub).'</td></tr></table>';
    }
  ?>
  <hr>
  <table>
    <?php if($tot_disk_item>0){ ?>
    <tr><td>Diskon item</td><td class="r">-<?php echo format_rupiah($tot_disk_item);?></td></tr>
    <?php } ?>
    <?php if($t['diskon_nota']>0){ ?>
    <tr><td>Diskon nota</td><td class="r">-<?php echo format_rupiah($t['diskon_nota']);?></td></tr>
    <?php } ?>
    <tr class="b"><td>TOTAL</td><td class="r"><?php echo format_rupiah($t['total']);?></td></tr>
    <tr><td>Metode</td><td class="r"><?php echo $t['metode_bayar'];?></td></tr>
    <?php if($t['metode_bayar']=='Hutang'){ ?>
      <tr><td>Bayar/DP</td><td class="r"><?php echo format_rupiah($t['bayar']);?></td></tr>
      <tr class="b"><td>Sisa Hutang</td><td class="r"><?php echo format_rupiah($t['total']-$t['bayar']);?></td></tr>
      <?php if($t['jatuh_tempo']){ ?><tr><td>J.Tempo</td><td class="r"><?php echo format_tanggal($t['jatuh_tempo']);?></td></tr><?php } ?>
    <?php }else{ ?>
      <tr><td>Bayar</td><td class="r"><?php echo format_rupiah($t['bayar']);?></td></tr>
      <tr><td>Kembali</td><td class="r"><?php echo format_rupiah($t['kembali']);?></td></tr>
    <?php } ?>
  </table>
  <hr>
  <div class="c"><?php echo htmlspecialchars($toko['footer_nota']);?></div>
</div>

<div class="toolbar">
  <button class="btn-print" onclick="window.print()">🖨️ Cetak</button>
  <?php
    // tombol WA aktif hanya bila ada nomor
    $wa = $t['wa_kon'] ? $t['wa_kon'] : $t['telp_kon'];
    if($wa){
      echo '<a class="btn-wa" href="kirim_wa.php?id='.$kode.'" target="_blank">💬 Kirim WA</a>';
    }
  ?>
  <a class="btn-new" href="trx_baru_v2.php">➕ Transaksi Baru</a>
</div>
</body>
</html>
