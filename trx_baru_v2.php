<?php
	include("sess_check.php");
	$pagedesc = "Transaksi Baru";
	include("layout_top.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	$kasir = $sess_kasirid;
	$tgl   = date('Y-m-d');

	// ---- helper aman untuk input (matching gaya lama, tapi lebih aman) ----
	function inp_int($v){ return intval($v); }
	function inp_str($conn,$v){ return mysqli_real_escape_string($conn, trim(strip_tags($v))); }

	// =================================================================
	// AKSI 1: TAMBAH ITEM KE KERANJANG (tmp_trx) + diskon per item
	// =================================================================
	if(isset($_POST['tambah_item'])){
		$id_brg = inp_int($_POST['id_brg']);
		$jml    = inp_int($_POST['jml']);
		$diskon = inp_int($_POST['diskon']);
		if($jml < 1) $jml = 1;
		if($id_brg > 0){
			// jika item sudah ada di keranjang -> tambah jumlahnya
			$cek = mysqli_query($conn, "SELECT id_tmp, jml FROM tmp_trx
				WHERE id_brg='$id_brg' AND id_kasir='$kasir' AND status='On Process' LIMIT 1");
			if(mysqli_num_rows($cek) > 0){
				$r = mysqli_fetch_array($cek);
				$new = $r['jml'] + $jml;
				mysqli_query($conn, "UPDATE tmp_trx SET jml='$new', diskon='$diskon'
					WHERE id_tmp='".$r['id_tmp']."'");
			}else{
				mysqli_query($conn, "INSERT INTO tmp_trx(id_trx,id_brg,jml,diskon,id_kasir,status)
					VALUES('0','$id_brg','$jml','$diskon','$kasir','On Process')");
			}
		}
		echo "<script>document.location='trx_baru_v2.php';</script>";
	}

	// =================================================================
	// AKSI 2: SIMPAN NOTA -> trx + trx_detail (snapshot) + kurangi stok
	// =================================================================
	if(isset($_POST['simpan'])){
		$kon        = inp_int($_POST['kon']);
		$tg         = inp_str($conn, $_POST['tgl']);
		$metode     = inp_str($conn, $_POST['metode_bayar']);
		$bayar      = inp_int($_POST['bayar']);
		$diskon_nota= inp_int($_POST['diskon_nota']);
		$jatuh_tempo= !empty($_POST['jatuh_tempo']) ? inp_str($conn,$_POST['jatuh_tempo']) : NULL;
		$plat       = inp_str($conn, $_POST['plat_nomor']);
		$catatan    = inp_str($conn, $_POST['catatan']);
		$no         = date('dmYHis').uniqid(); // ditambahkan uniqid() untuk menghindari bentrok id_trx

			// Mulai transaksi
			mysqli_begin_transaction($conn);

		// Ambil isi keranjang + data barang (harga terkini utk snapshot)
		$sql = "SELECT tmp_trx.*, barangjasa.nama, barangjasa.jenis, barangjasa.harga, barangjasa.stok
				FROM tmp_trx JOIN barangjasa ON tmp_trx.id_brg=barangjasa.id_brg
				WHERE tmp_trx.id_kasir='$kasir' AND tmp_trx.status='On Process'";
		$q = mysqli_query($conn, $sql);

		if(mysqli_num_rows($q) == 0){
			echo "<script>alert('Keranjang masih kosong.');document.location='trx_baru_v2.php';</script>";
		}else{
			$grand = 0;
			$rows  = array();
			while($d = mysqli_fetch_array($q)){ $rows[] = $d; }

			// Hitung grand total dari (harga - diskon) * jml
			foreach($rows as $d){
				$diskon_clamp = max(0, min($d['diskon'], $d['harga'])); // validasi diskon tidak negatif & tidak lebih dari harga
				$grand += (($d['harga'] - $diskon_clamp) * $d['jml']);
			}
			$total_akhir = $grand - $diskon_nota;
			if($total_akhir < 0) $total_akhir = 0;

			// Tentukan status pelunasan & kembalian
			if($metode == 'Hutang'){
				$status_bayar = 'Belum Lunas';
				$bayar_final  = ($bayar > 0 ? $bayar : 0); // boleh DP
				$kembali      = 0;
			}else{
				$status_bayar = 'Lunas';
				$bayar_final  = ($bayar > 0 ? $bayar : $total_akhir);
				$kembali      = $bayar_final - $total_akhir;
				if($kembali < 0) $kembali = 0;
				$jatuh_tempo  = NULL;
			}

			$jt = is_null($jatuh_tempo) ? "NULL" : "'$jatuh_tempo'";

			// 1) Simpan header transaksi
			$r1 = mysqli_query($conn, "INSERT INTO trx
				(id_trx,id_kon,tgl_trx,total,id_kasir,metode_bayar,bayar,kembali,diskon_nota,status_bayar,jatuh_tempo,plat_nomor,catatan)
				VALUES('$no','$kon','$tg','$total_akhir','$kasir','$metode','$bayar_final','$kembali','$diskon_nota','$status_bayar',$jt,'$plat','$catatan')");
			if(!$r1){
				mysqli_rollback($conn);
				echo "<script>alert('Gagal menyimpan header transaksi.'); document.location='trx_baru_v2.php';</script>";
				exit;
			}

			// 2) Simpan tiap baris ke trx_detail (SNAPSHOT) + kurangi stok
			foreach($rows as $d){
				$sub = ($d['harga'] - $d['diskon']) * $d['jml'];
				if($sub < 0) $sub = 0; // clamp subtotal ke 0 minimum
				$nama  = mysqli_real_escape_string($conn, $d['nama']);
				$jenis = mysqli_real_escape_string($conn, $d['jenis']);
				$r1 = mysqli_query($conn, "INSERT INTO trx_detail
					(id_trx,id_brg,nama_snap,jenis_snap,harga_snap,diskon,jml,subtotal)
					VALUES('$no','".$d['id_brg']."','$nama','$jenis','".$d['harga']."','".$d['diskon']."','".$d['jml']."','$sub')");

				if($d['jenis'] == 'barang'){
					// validasi stok: jangan kurangi jika melebihi stok
					if($d['jml'] > intval($d['stok'])){
						mysqli_rollback($conn);
						echo "<script>alert('Jumlah item melebihi stok tersedia.'); document.location='trx_baru_v2.php';</script>";
						exit;
					}
					$new_stok = $d['stok'] - $d['jml'];
					mysqli_query($conn, "UPDATE barangjasa SET stok='$new_stok' WHERE id_brg='".$d['id_brg']."'");
				}
			}

			// 3) Kosongkan keranjang kasir ini
			mysqli_query($conn, "DELETE FROM tmp_trx WHERE id_kasir='$kasir' AND status='On Process'");

			// Commit transaksi
			mysqli_commit($conn);

			// 4) Arahkan ke struk (bisa langsung cetak / kirim WA)
			echo "<script>document.location='struk_thermal.php?id=$no';</script>";
		}
	}
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row"><div class="col-lg-12"><h1 class="page-header">Transaksi Baru</h1></div></div>
    <div class="row"><div class="col-lg-12"><?php include("layout_alert.php"); ?></div></div>

    <!-- ============ FORM TAMBAH ITEM ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-primary">
          <div class="panel-heading">Tambah Barang / Jasa</div>
          <div class="panel-body">
            <form method="POST" class="form-inline" onsubmit="return validItem()">
              <div class="form-group">
                <label>Item</label>
                <select name="id_brg" id="id_brg" class="form-control" required onchange="setHarga()">
                  <option value="">== Pilih Barang/Jasa ==</option>
                  <?php
                    $lb = mysqli_query($conn,"SELECT id_brg,nama,jenis,harga,stok FROM barangjasa ORDER BY nama ASC");
                    while($b = mysqli_fetch_array($lb)){
                      $label = $b['nama']." (".$b['jenis'].") - ".format_rupiah($b['harga']);
                      if($b['jenis']=='barang') $label .= " | stok:".$b['stok'];
                      echo '<option value="'.$b['id_brg'].'" data-harga="'.$b['harga'].'">'.$label.'</option>';
                    }
                  ?>
                </select>
              </div>
              <div class="form-group">
                <label>Jumlah</label>
                <input type="number" name="jml" id="jml" class="form-control" value="1" min="1" style="width:80px" oninput="preview()">
              </div>
              <div class="form-group">
                <label>Diskon/unit (Rp)</label>
                <input type="number" name="diskon" id="diskon" class="form-control" value="0" min="0" style="width:120px" oninput="preview()">
              </div>
              <div class="form-group">
                <label>Subtotal</label>
                <input type="text" id="preview_sub" class="form-control" value="Rp0" readonly style="width:140px;font-weight:bold">
              </div>
              <button type="submit" name="tambah_item" class="btn btn-warning">+ Tambah</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ KERANJANG + CHECKOUT ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <form method="POST" onsubmit="return validSimpan()">
          <div class="panel-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th width="1%">No</th><th>Nama</th><th class="text-center">Jumlah</th>
                  <th class="text-right">Harga</th><th class="text-right">Diskon/unit</th>
                  <th class="text-right">Subtotal</th><th class="text-center">Opsi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $i=1; $grand=0;
                  $sqlc = "SELECT tmp_trx.*, barangjasa.nama, barangjasa.harga
                           FROM tmp_trx JOIN barangjasa ON tmp_trx.id_brg=barangjasa.id_brg
                           WHERE tmp_trx.status='On Process' AND tmp_trx.id_kasir='$kasir'
                           ORDER BY barangjasa.nama ASC";
                  $rc = mysqli_query($conn,$sqlc);
                  while($c = mysqli_fetch_array($rc)){
                    $sub = ($c['harga'] - $c['diskon']) * $c['jml'];
                    $grand += $sub;
                    echo '<tr>';
                    echo '<td class="text-center">'.$i.'</td>';
                    echo '<td>'.$c['nama'].'</td>';
                    echo '<td class="text-center">'.$c['jml'].'</td>';
                    echo '<td class="text-right">'.format_rupiah($c['harga']).'</td>';
                    echo '<td class="text-right">'.format_rupiah($c['diskon']).'</td>';
                    echo '<td class="text-right">'.format_rupiah($sub).'</td>';
                    echo '<td class="text-center"><a href="trxtmp_hapus.php?id='.$c['id_tmp'].'" onclick="return confirm(\'Hapus item ini?\')" class="btn btn-danger btn-xs">Hapus</a></td>';
                    echo '</tr>';
                    $i++;
                  }
                ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="5" class="text-right">Total Kotor</th>
                  <th class="text-right" id="grand" data-grand="<?php echo $grand;?>"><?php echo format_rupiah($grand);?></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>

            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Konsumen</label>
                  <select name="kon" id="kon" class="form-control" required onchange="autoPlat()">
                    <option value="">== Pilih Konsumen ==</option>
                    <option value="0" data-plat="">Umum</option>
                    <?php
                      $lk = mysqli_query($conn,"SELECT id_kon,nama_kon,plat_nomor FROM konsumen WHERE id_kon!='0' ORDER BY nama_kon ASC");
                      while($k = mysqli_fetch_array($lk)){
                        echo '<option value="'.$k['id_kon'].'" data-plat="'.htmlspecialchars($k['plat_nomor']).'">'.$k['nama_kon'].'</option>';
                      }
                    ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Plat Nomor Kendaraan</label>
                  <input type="text" name="plat_nomor" id="plat_nomor" class="form-control" placeholder="mis. B 1234 XYZ">
                </div>
                <div class="form-group">
                  <label>Catatan (opsional)</label>
                  <textarea name="catatan" class="form-control" rows="2"></textarea>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Diskon Nota (Rp)</label>
                  <input type="number" name="diskon_nota" id="diskon_nota" class="form-control" value="0" min="0" oninput="hitung()">
                </div>
                <div class="form-group">
                  <label>Metode Bayar</label>
                  <select name="metode_bayar" id="metode_bayar" class="form-control" onchange="hitung()">
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer">Transfer</option>
                    <option value="Hutang">Hutang / Bon</option>
                  </select>
                </div>
                <div class="form-group" id="row_tempo" style="display:none">
                  <label>Jatuh Tempo (untuk Hutang)</label>
                  <input type="date" name="jatuh_tempo" id="jatuh_tempo" class="form-control">
                </div>
                <div class="form-group">
                  <label><b id="lbl_total">Total Bayar</b></label>
                  <input type="text" id="total_view" class="form-control" value="Rp0" readonly style="font-weight:bold;font-size:18px">
                </div>
                <div class="form-group">
                  <label id="lbl_bayar">Uang Dibayar (Rp)</label>
                  <input type="number" name="bayar" id="bayar" class="form-control" value="0" min="0" oninput="hitung()">
                </div>
                <div class="form-group">
                  <label id="lbl_kembali">Kembalian</label>
                  <input type="text" id="kembali_view" class="form-control" value="Rp0" readonly>
                </div>
                <input type="hidden" name="tgl" value="<?php echo $tgl;?>">
              </div>
            </div>
          </div>
          <div class="panel-footer">
            <button type="submit" name="simpan" class="btn btn-success btn-lg">Simpan & Cetak Struk</button>
          </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function rp(n){ return 'Rp'+ (n||0).toLocaleString('id-ID'); }

// preview subtotal item yg mau ditambah
function setHarga(){ preview(); }
function preview(){
  var opt = document.getElementById('id_brg').selectedOptions[0];
  var h = opt ? parseInt(opt.getAttribute('data-harga')||0) : 0;
  var j = parseInt(document.getElementById('jml').value||0);
  var d = parseInt(document.getElementById('diskon').value||0);
  var s = (h - d) * j; if(s<0) s=0;
  document.getElementById('preview_sub').value = rp(s);
}
function autoPlat(){
  var opt = document.getElementById('kon').selectedOptions[0];
  var p = opt ? (opt.getAttribute('data-plat')||'') : '';
  if(p) document.getElementById('plat_nomor').value = p;
}
// perhitungan real-time checkout
function hitung(){
  var grand = parseInt(document.getElementById('grand').getAttribute('data-grand')||0);
  var dn = parseInt(document.getElementById('diskon_nota').value||0);
  var total = grand - dn; if(total<0) total=0;
  document.getElementById('total_view').value = rp(total);

  var metode = document.getElementById('metode_bayar').value;
  document.getElementById('row_tempo').style.display = (metode=='Hutang') ? 'block' : 'none';
  document.getElementById('lbl_bayar').innerText = (metode=='Hutang') ? 'Uang Muka / DP (Rp)' : 'Uang Dibayar (Rp)';

  var bayar = parseInt(document.getElementById('bayar').value||0);
  var kembali = (metode=='Hutang') ? 0 : (bayar - total);
  if(kembali<0) kembali=0;
  document.getElementById('kembali_view').value = rp(kembali);
  window._total = total; window._metode = metode; window._bayar = bayar;
}
function validItem(){
  if(!document.getElementById('id_brg').value){ alert('Pilih item dulu.'); return false; }
  return true;
}
function validSimpan(){
  var g = parseInt(document.getElementById('grand').getAttribute('data-grand')||0);
  if(g<=0){ alert('Keranjang masih kosong.'); return false; }
  if(window._metode!='Hutang' && (window._bayar < window._total)){
    if(!confirm('Uang dibayar kurang dari total. Lanjut sebagai kurang bayar?')) return false;
  }
  return true;
}
document.addEventListener('DOMContentLoaded', function(){ preview(); hitung(); });
</script>
<?php include("layout_bottom.php"); ?>
