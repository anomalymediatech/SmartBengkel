<?php
include("sess_check.php");

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
if($id === '') { header("Location: piutang.php"); exit; }

$sql = "SELECT trx.*, ko.nama_kon, ko.telp_kon, ko.wa_kon, ko.alamat_kon, ks.nama_kasir
        FROM trx
        LEFT JOIN konsumen ko ON trx.id_kon=ko.id_kon
        JOIN kasir ks ON trx.id_kasir=ks.id_kasir
        WHERE trx.id_trx='$id' AND trx.metode_bayar='Hutang'
        LIMIT 1";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) {
    echo "<div class='alert alert-danger'>Transaksi piutang tidak ditemukan</div>";
    include("layout_bottom.php");
    exit;
}
$t = mysqli_fetch_array($res);

$bayar_cicilan = (int)mysqli_fetch_array(mysqli_query($conn, "SELECT SUM(jumlah) FROM bayar_piutang WHERE id_trx='$id'"))[0];
$total_bayar = $t['bayar'] + $bayar_cicilan;
$sisa = $t['total'] - $total_bayar;

$sql_cicil = "SELECT bp.*, k.nama_kasir FROM bayar_piutang bp
              JOIN kasir k ON bp.id_kasir=k.id_kasir
              WHERE bp.id_trx='$id' ORDER BY bp.tgl ASC";
$res_cicil = mysqli_query($conn, $sql_cicil);

$sql_detail = "SELECT * FROM trx_detail WHERE id_trx='$id'";
$res_detail = mysqli_query($conn, $sql_detail);

$pagedesc = "Detail Piutang";
$menuparent = "piutang";
include("layout_top.php");
include("dist/function/format_rupiah.php");
include("dist/function/format_tanggal.php");
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Detail Piutang - <?php echo $id; ?></h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Info Transaksi -->
        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-info-circle"></i> Info Transaksi</div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr><th width="20%">No. Nota</th><td><?php echo $id; ?></td></tr>
                            <tr><th>Tanggal</th><td><?php echo format_tanggal($t['tgl_trx']); ?></td></tr>
                            <tr><th>Kasir</th><td><?php echo htmlspecialchars($t['nama_kasir']); ?></td></tr>
                            <tr><th>Pelanggan</th><td><?php echo htmlspecialchars($t['nama_kon'] ?: 'Umum'); ?></td></tr>
                            <tr><th>Telepon</th><td><?php echo htmlspecialchars($t['telp_kon']); ?></td></tr>
                            <tr><th>WhatsApp</th><td><?php echo htmlspecialchars($t['wa_kon']); ?></td></tr>
                            <tr><th>Alamat</th><td><?php echo htmlspecialchars($t['alamat_kon']); ?></td></tr>
                            <tr><th>Plat Nomor</th><td><?php echo htmlspecialchars($t['plat_nomor']); ?></td></tr>
                            <tr><th>Metode Bayar</th><td><span class="label label-warning"><?php echo $t['metode_bayar']; ?></span></td></tr>
                            <tr><th>Status</th><td><span class="label <?php echo $t['status_bayar']=='Lunas'?'label-success':'label-warning'; ?>"><?php echo $t['status_bayar']; ?></span></td></tr>
                            <?php if($t['jatuh_tempo']): ?>
                            <tr><th>Jatuh Tempo</th><td><?php echo format_tanggal($t['jatuh_tempo']); ?></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-money"></i> Ringkasan Keuangan</div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr><th>Total Tagihan</th><td class="text-right"><strong><?php echo format_rupiah($t['total']); ?></strong></td></tr>
                            <tr><th>DP / Bayar Awal</th><td class="text-right"><?php echo format_rupiah($t['bayar']); ?></td></tr>
                            <tr><th>Cicilan Terbayar</th><td class="text-right text-success"><?php echo format_rupiah($bayar_cicilan); ?></td></tr>
                            <tr class="info"><th>Total Terbayar</th><td class="text-right"><strong><?php echo format_rupiah($total_bayar); ?></strong></td></tr>
                            <tr class="danger"><th>SISA PIUTANG</th><td class="text-right"><strong><?php echo format_rupiah($sisa); ?></strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Item -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-list"></i> Detail Barang/Jasa</div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th>Nama</th>
                                    <th class="text-center">Jenis</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-right">Diskon</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                while($d = mysqli_fetch_array($res_detail)) {
                                    echo '<tr>';
                                    echo '<td class="text-center">'.$i++.'</td>';
                                    echo '<td>'.htmlspecialchars($d['nama_snap']).'</td>';
                                    echo '<td class="text-center"><span class="label label-info">'.$d['jenis_snap'].'</span></td>';
                                    echo '<td class="text-center">'.$d['jml'].'</td>';
                                    echo '<td class="text-right">'.format_rupiah($d['harga_snap']).'</td>';
                                    echo '<td class="text-right">'.($d['diskon'] > 0 ? format_rupiah($d['diskon']) : '-').'</td>';
                                    echo '<td class="text-right"><strong>'.format_rupiah($d['subtotal']).'</strong></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Cicilan -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-history"></i> Riwayat Cicilan</div>
                    <div class="panel-body">
                        <?php if(mysqli_num_rows($res_cicil) > 0): ?>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th width="15%">Tanggal</th>
                                    <th class="text-right">Jumlah</th>
                                    <th>Kasir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                while($c = mysqli_fetch_array($res_cicil)) {
                                    echo '<tr>';
                                    echo '<td class="text-center">'.$i++.'</td>';
                                    echo '<td>'.format_tanggal($c['tgl']).'</td>';
                                    echo '<td class="text-right text-success"><strong>'.format_rupiah($c['jumlah']).'</strong></td>';
                                    echo '<td>'.htmlspecialchars($c['nama_kasir']).'</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted text-center">Belum ada pembayaran cicilan.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($sisa > 0): ?>
                    <div class="panel-footer">
                        <a href="piutang_bayar.php?id=<?php echo $id; ?>" class="btn btn-success"><i class="fa fa-money"></i> Catat Cicilan</a>
                        <a href="../struk_thermal.php?id=<?php echo $id; ?>" class="btn btn-default" target="_blank"><i class="fa fa-print"></i> Cetak Struk</a>
                        <a href="piutang.php" class="btn btn-default">Kembali</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include("layout_bottom.php"); ?>