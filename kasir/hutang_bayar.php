<?php
include("sess_check.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id == 0) { header("Location: hutang.php"); exit; }

// Ambil data hutang
$sql = "SELECT h.*, s.nama_spl FROM hutang_supplier h JOIN supplier s ON h.id_spl=s.id_spl WHERE h.id_hutang=$id";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) { header("Location: hutang.php"); exit; }
$h = mysqli_fetch_array($res);

$terbayar = (int)mysqli_fetch_array(mysqli_query($conn, "SELECT SUM(jumlah) FROM bayar_hutang WHERE id_hutang=$id"))[0];
$sisa = $h['total'] - $terbayar;

$pagedesc = "Bayar Cicilan Hutang Supplier";
$menuparent = "hutang";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Bayar Cicilan Hutang Supplier</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-heading">Info Hutang</div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr><th width="30%">Supplier</th><td><?php echo htmlspecialchars($h['nama_spl']); ?></td></tr>
                            <tr><th>Tanggal Hutang</th><td><?php echo format_tanggal($h['tgl']); ?></td></tr>
                            <tr><th>Keterangan</th><td><?php echo htmlspecialchars($h['keterangan']); ?></td></tr>
                            <tr class="info"><th>Total Hutang</th><td><?php echo format_rupiah($h['total']); ?></td></tr>
                            <tr class="success"><th>Sudah Dibayar</th><td><?php echo format_rupiah($terbayar); ?></td></tr>
                            <tr class="danger"><th>Sisa Hutang</th><td><strong><?php echo format_rupiah($sisa); ?></strong></td></tr>
                            <tr><th>Jatuh Tempo</th><td><?php echo $h['jatuh_tempo'] ? format_tanggal($h['jatuh_tempo']) : '-'; ?></td></tr>
                            <tr><th>Status</th><td><span class="label <?php echo $h['status_bayar']=='Lunas'?'label-success':'label-warning'; ?>"><?php echo $h['status_bayar']; ?></span></td></tr>
                        </table>
                    </div>
                </div>

                <?php if($sisa > 0): ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">Input Pembayaran</div>
                    <div class="panel-body">
                        <form method="POST" action="hutang_bayar_proses.php">
                            <input type="hidden" name="id_hutang" value="<?php echo $id; ?>">
                            <input type="hidden" name="sisa" value="<?php echo $sisa; ?>">
                            
                            <div class="form-group">
                                <label>Tanggal Bayar</label>
                                <input type="date" name="tgl" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Jumlah Bayar (Rp)</label>
                                <input type="number" name="jumlah" class="form-control" min="1" max="<?php echo $sisa; ?>" placeholder="Maksimal: <?php echo format_rupiah($sisa); ?>" required>
                                <small class="text-muted">Sisa: <strong><?php echo format_rupiah($sisa); ?></strong></small>
                            </div>
                            <button type="submit" name="bayar" class="btn btn-success btn-lg">Simpan Pembayaran</button>
                            <a href="hutang.php" class="btn btn-default">Batal</a>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Riwayat Pembayaran -->
                <div class="panel panel-default">
                    <div class="panel-heading">Riwayat Pembayaran</div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr><th width="1%">No</th><th>Tanggal</th><th class="text-right">Jumlah</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $sql = "SELECT * FROM bayar_hutang WHERE id_hutang=$id ORDER BY tgl DESC";
                                $res = mysqli_query($conn, $sql);
                                while($b = mysqli_fetch_array($res)) {
                                    echo '<tr>';
                                    echo '<td class="text-center">'.$i++.'</td>';
                                    echo '<td>'.format_tanggal($b['tgl']).'</td>';
                                    echo '<td class="text-right text-success">'.format_rupiah($b['jumlah']).'</td>';
                                    echo '</tr>';
                                }
                                if($i == 1) echo '<tr><td colspan="3" class="text-center text-muted">Belum ada pembayaran</td></tr>';
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