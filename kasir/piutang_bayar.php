<?php
include("sess_check.php");

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
if($id === '') { header("Location: piutang.php"); exit; }

// Ambil data transaksi
$sql = "SELECT trx.*, ko.nama_kon, ko.telp_kon, ko.wa_kon
        FROM trx
        LEFT JOIN konsumen ko ON trx.id_kon=ko.id_kon
        WHERE trx.id_trx='$id' AND trx.metode_bayar='Hutang'
        LIMIT 1";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) { header("Location: piutang.php"); exit; }
$t = mysqli_fetch_array($res);

// Hitung sisa piutang
$totalBayar = $t['bayar'] + (int)mysqli_fetch_array(mysqli_query($conn, "SELECT SUM(jumlah) FROM bayar_piutang WHERE id_trx='$id'"))[0];
$sisa = $t['total'] - $totalBayar;

$pagedesc = "Cicilan Piutang";
$menuparent = "piutang";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Cicilan Piutang Pelanggan</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-heading">Info Transaksi</div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr><th width="30%">No. Nota</th><td><?php echo htmlspecialchars($t['id_trx']); ?></td></tr>
                            <tr><th>Tanggal</th><td><?php echo format_tanggal($t['tgl_trx']); ?></td></tr>
                            <tr><th>Pelanggan</th><td><?php echo htmlspecialchars($t['nama_kon']); ?><br><small><?php echo htmlspecialchars($t['telp_kon']); ?> / <?php echo htmlspecialchars($t['wa_kon']); ?></small></td></tr>
                            <tr><th>Plat Nomor</th><td><?php echo htmlspecialchars($t['plat_nomor']); ?></td></tr>
                            <tr class="info"><th>Total Tagihan</th><td><?php echo format_rupiah($t['total']); ?></td></tr>
                            <tr class="success"><th>DP / Sudah Bayar</th><td><?php echo format_rupiah($totalBayar); ?></td></tr>
                            <tr class="danger"><th>Sisa Piutang</th><td><strong><?php echo format_rupiah($sisa); ?></strong></td></tr>
                            <tr><th>Jatuh Tempo</th><td><?php echo $t['jatuh_tempo'] ? format_tanggal($t['jatuh_tempo']) : '-'; ?></td></tr>
                            <tr><th>Status</th><td><span class="label <?php echo $t['status_bayar']=='Lunas'?'label-success':'label-warning'; ?>"><?php echo $t['status_bayar']; ?></span></td></tr>
                        </table>
                    </div>
                </div>

                <?php if($sisa > 0): ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">Input Cicilan</div>
                    <div class="panel-body">
                        <form method="POST" action="piutang_bayar_proses.php">
                            <input type="hidden" name="id_trx" value="<?php echo htmlspecialchars($id); ?>">
                            <input type="hidden" name="sisa" value="<?php echo $sisa; ?>">
                            
                            <div class="form-group">
                                <label>Tanggal Bayar</label>
                                <input type="date" name="tgl" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Jumlah Cicilan (Rp)</label>
                                <input type="number" name="jumlah" class="form-control" min="1" max="<?php echo $sisa; ?>" placeholder="Maksimal: <?php echo format_rupiah($sisa); ?>" required>
                                <small class="text-muted">Sisa piutang: <strong><?php echo format_rupiah($sisa); ?></strong></small>
                            </div>
                            <button type="submit" name="bayar" class="btn btn-success btn-lg">Simpan Cicilan</button>
                            <a href="piutang.php" class="btn btn-default">Batal</a>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Riwayat Cicilan -->
                <div class="panel panel-default">
                    <div class="panel-heading">Riwayat Cicilan</div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr><th width="1%">No</th><th>Tanggal</th><th class="text-right">Jumlah</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $sql = "SELECT * FROM bayar_piutang WHERE id_trx='$id' ORDER BY tgl DESC";
                                $res = mysqli_query($conn, $sql);
                                while($b = mysqli_fetch_array($res)) {
                                    echo '<tr>';
                                    echo '<td class="text-center">'.$i++.'</td>';
                                    echo '<td>'.format_tanggal($b['tgl']).'</td>';
                                    echo '<td class="text-right text-success">'.format_rupiah($b['jumlah']).'</td>';
                                    echo '</tr>';
                                }
                                if($i == 1) echo '<tr><td colspan="3" class="text-center text-muted">Belum ada cicilan</td></tr>';
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("layout_bottom.php"); ?>