<?php
include("sess_check.php");
include("dist/function/format_rupiah.php");
include("dist/function/format_tanggal.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id == 0) { exit('ID tidak valid'); }

$sql = "SELECT sg.*, p.nama, p.jabatan, p.gaji_pokok as gp_db, p.tarif_lembur as tl_db
        FROM slip_gaji sg
        JOIN pegawai p ON sg.id_pegawai=p.id_pegawai
        WHERE sg.id_slip=$id";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) { exit('Slip gaji tidak ditemukan'); }
$s = mysqli_fetch_array($res);

// Ambil data toko untuk header
$toko_q = mysqli_query($conn,"SELECT * FROM toko WHERE id_toko=1 LIMIT 1");
$toko = mysqli_num_rows($toko_q) ? mysqli_fetch_array($toko_q)
       : array('nama_toko'=>'Bengkel','alamat'=>'','telp'=>'','footer_nota'=>'Terima kasih');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Slip Gaji <?php echo $s['id_slip'];?></title>
<style>
  body{ background:#e9e9e9; font-family:Arial,sans-serif; }
  .slip{
    width:210mm; background:#fff; margin:16px auto; padding:15mm;
    font-family:'Courier New',monospace; font-size:12px; color:#000; line-height:1.5;
  }
  .c{ text-align:center; } .r{ text-align:right; } .b{ font-weight:bold; }
  .slip hr{ border:none; border-top:1px dashed #000; margin:8px 0; }
  .slip table{ width:100%; border-collapse:collapse; }
  .slip td{ vertical-align:top; padding:2px 0; }
  .item td{ font-size:12px; }
  .toolbar{ width:210mm; margin:16px auto; text-align:center; }
  .toolbar a,.toolbar button{
    display:inline-block; margin:4px; padding:10px 16px; font-size:13px;
    border:none; border-radius:4px; cursor:pointer; text-decoration:none; color:#fff;
  }
  .btn-print{ background:#337ab7; } .btn-wa{ background:#25D366; }
  @media print{
    body{ background:#fff; }
    .toolbar{ display:none; }
    .slip{ margin:0; width:210mm; }
    @page{ margin:0; size:210mm 297mm; }
  }
</style>
</head>
<body>
<div class="slip" id="slip">
  <div class="c b" style="font-size:16px; margin-bottom:4px"><?php echo strtoupper(htmlspecialchars($toko['nama_toko']));?></div>
  <div class="c">SLIP GAJI PEGAWAI</div>
  <div class="c"><?php echo htmlspecialchars($toko['alamat']);?></div>
  <div class="c">Telp: <?php echo htmlspecialchars($toko['telp']);?></div>
  <hr>
  <table>
    <tr><td width="35%">No. Slip</td><td class="r"><?php echo $s['id_slip'];?></td></tr>
    <tr><td>Periode</td><td class="r"><?php echo htmlspecialchars($s['periode']);?></td></tr>
    <tr><td>Tgl Bayar</td><td class="r"><?php echo format_tanggal($s['tgl_bayar']);?></td></tr>
    <tr><td>Pegawai</td><td class="r"><?php echo htmlspecialchars($s['nama']);?> (<?php echo htmlspecialchars($s['jabatan']);?>)</td></tr>
  </table>
  <hr>
  <table>
    <tr class="b"><td width="60%">KOMPONEN</td><td width="40%" class="r">JUMLAH (Rp)</td></tr>
    <tr><td>Gaji Pokok</td><td class="r"><?php echo format_rupiah($s['gaji_pokok']);?></td></tr>
    <tr><td>Lembur (<?php echo $s['jam_lembur'];?> jam × <?php echo format_rupiah($s['tarif_lembur']);?>)</td><td class="r"><?php echo format_rupiah($s['bonus_lembur']);?></td></tr>
    <tr class="b"><td>TOTAL PENDAPATAN</td><td class="r"><?php echo format_rupiah($s['gaji_pokok'] + $s['bonus_lembur']);?></td></tr>
    <tr><td>Potongan</td><td class="r">- <?php echo format_rupiah($s['potongan']);?></td></tr>
    <tr class="b" style="font-size:14px"><td>GAJI BERSIH</td><td class="r"><?php echo format_rupiah($s['gaji_bersih']);?></td></tr>
  </table>
  <hr>
  <div class="c"><?php echo htmlspecialchars($toko['footer_nota']);?></div>
  <br><br>
  <table width="100%">
    <tr>
      <td class="c" width="50%">Disetujui,<br><br><br><br><br><u><?php echo htmlspecialchars($toko['nama_toko']);?></u></td>
      <td class="c" width="50%">Diterima,<br><br><br><br><br><u><?php echo htmlspecialchars($s['nama']);?></u></td>
    </tr>
  </table>
</div>

<div class="toolbar">
  <button class="btn-print" onclick="window.print()">🖨️ Cetak</button>
  <?php
    $wa = $s['wa_kon'] ?? '';
    // Cek apakah pegawai punya WA di tabel pegawai - belum ada kolom wa di pegawai, skip
    // jika perlu, bisa tambah kolom wa di tabel pegawai
  ?>
</div>
</body>
</html>