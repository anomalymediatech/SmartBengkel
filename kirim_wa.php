<?php
	include("sess_check.php");
	include("dist/function/format_tanggal.php");
	include("dist/function/format_rupiah.php");

	$kode = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
	if($kode == ''){ echo "Nomor transaksi kosong."; exit; }

	// normalisasi nomor HP Indonesia -> format internasional 62xxxx
	function wa_normalize($no){
		$no = preg_replace('/[^0-9]/', '', $no);   // buang spasi, +, -, dll
		if(substr($no,0,1) == '0'){ $no = '62'.substr($no,1); }
		elseif(substr($no,0,2) == '62'){ /* sudah benar */ }
		elseif(substr($no,0,1) == '8'){ $no = '62'.$no; }
		return $no;
	}

	$h = mysqli_query($conn, "SELECT trx.*, konsumen.nama_kon, konsumen.wa_kon, konsumen.telp_kon,
			kasir.nama_kasir FROM trx
			JOIN kasir ON trx.id_kasir=kasir.id_kasir
			LEFT JOIN konsumen ON trx.id_kon=konsumen.id_kon
			WHERE trx.id_trx='$kode' LIMIT 1");
	if(mysqli_num_rows($h)==0){ echo "Transaksi tidak ditemukan."; exit; }
	$t = mysqli_fetch_array($h);

	$toko_q = mysqli_query($conn,"SELECT * FROM toko WHERE id_toko=1 LIMIT 1");
	$toko = mysqli_num_rows($toko_q) ? mysqli_fetch_array($toko_q) : array('nama_toko'=>'Bengkel','footer_nota'=>'Terima kasih');

	$nomor = wa_normalize($t['wa_kon'] ? $t['wa_kon'] : $t['telp_kon']);
	if($nomor == ''){ echo "Nomor WhatsApp pelanggan belum diisi."; exit; }

	// ---- susun teks struk (pakai *bold* WhatsApp) ----
	$L = array();
	$L[] = "*".strtoupper($toko['nama_toko'])."*";
	$L[] = "Nota: ".$t['id_trx'];
	$L[] = "Tgl: ".format_tanggal($t['tgl_trx']);
	$L[] = "Pelanggan: ".($t['nama_kon'] ? $t['nama_kon'] : 'Umum');
	if($t['plat_nomor']) $L[] = "Plat: ".$t['plat_nomor'];
	$L[] = "--------------------------";

	$d = mysqli_query($conn,"SELECT * FROM trx_detail WHERE id_trx='$kode'");
	while($it = mysqli_fetch_array($d)){
		$L[] = $it['nama_snap'];
		$baris = "  ".$it['jml']." x ".format_rupiah($it['harga_snap']);
		if($it['diskon']>0) $baris .= " (-".format_rupiah($it['diskon']).")";
		$baris .= "  = ".format_rupiah($it['subtotal']);
		$L[] = $baris;
	}
	$L[] = "--------------------------";
	if($t['diskon_nota']>0) $L[] = "Diskon nota: -".format_rupiah($t['diskon_nota']);
	$L[] = "*TOTAL: ".format_rupiah($t['total'])."*";
	$L[] = "Metode: ".$t['metode_bayar'];
	if($t['metode_bayar']=='Hutang'){
		$L[] = "Bayar/DP: ".format_rupiah($t['bayar']);
		$L[] = "*Sisa Hutang: ".format_rupiah($t['total']-$t['bayar'])."*";
		if($t['jatuh_tempo']) $L[] = "Jatuh tempo: ".format_tanggal($t['jatuh_tempo']);
	}else{
		$L[] = "Bayar: ".format_rupiah($t['bayar']);
		$L[] = "Kembali: ".format_rupiah($t['kembali']);
	}
	$L[] = "--------------------------";
	$L[] = $toko['footer_nota'];

	$teks = implode("\n", $L);
	$url  = "https://wa.me/".$nomor."?text=".rawurlencode($teks);

	header("Location: ".$url);
	exit;
?>
