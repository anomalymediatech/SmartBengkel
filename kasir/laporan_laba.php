<?php
include("sess_check.php");

$pagedesc = "Laporan Laba Kotor";
$menuparent = "laporan";
include("layout_top.php");
include("dist/function/format_rupiah.php");
include("dist/function/format_tanggal.php");

// Filter tanggal
$tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'semua'; // semua / barang / jasa

$where = "tgl_trx >= '$tgl_dari' AND tgl_trx <= '$tgl_sampai' AND status_bayar != 'Batal'";
if($jenis !== 'semua') {
    // Need to join with trx_detail for jenis filter
}

// Query laba kotor per transaksi (pakai trx_detail yang sudah ada snapshot harga_modal)
$sql = "SELECT 
            t.id_trx,
            t.tgl_trx,
            t.total,
            COALESCE(SUM(td.subtotal),0) as subtotal_jual,
            COALESCE(SUM(td.harga_modal_snap * td.jml),0) as modal_total,
            (COALESCE(SUM(td.subtotal),0) - COALESCE(SUM(td.harga_modal_snap * td.jml),0)) as laba_kotor,
            t.metode_bayar,
            k.nama_kon
        FROM trx t
        LEFT JOIN trx_detail td ON t.id_trx = td.id_trx
        LEFT JOIN konsumen k ON t.id_kon = k.id_kon
        WHERE $where
        GROUP BY t.id_trx
        ORDER BY t.tgl_trx DESC, t.id_trx DESC";
$res = mysqli_query($conn, $sql);

// Summary
$total_jual = 0;
$total_modal = 0;
$total_laba = 0;
$data = [];
while($row = mysqli_fetch_array($res)) {
    $data[] = $row;
    $total_jual += $row['subtotal_jual'];
    $total_modal += $row['modal_total'];
    $total_laba += $row['laba_kotor'];
}
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Laporan Laba Kotor</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Filter Form -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Filter Laporan</div>
                    <div class="panel-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group">
                                <label>Dari Tgl</label>
                                <input type="date" name="tgl_dari" class="form-control" value="<?php echo htmlspecialchars($tgl_dari); ?>">
                            </div>
                            <div class="form-group">
                                <label>Sampai Tgl</label>
                                <input type="date" name="tgl_sampai" class="form-control" value="<?php echo htmlspecialchars($tgl_sampai); ?>">
                            </div>
                            <div class="form-group">
                                <label>Jenis</label>
                                <select name="jenis" class="form-control">
                                    <option value="semua" <?php echo $jenis=='semua'?'selected':''; ?>>Semua</option>
                                    <option value="barang" <?php echo $jenis=='barang'?'selected':''; ?>>Barang</option>
                                    <option value="jasa" <?php echo $jenis=='jasa'?'selected':''; ?>>Jasa</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                            <a href="laporan_laba.php" class="btn btn-default">Reset</a>
                            <a href="laporan_laba_cetak.php?tgl_dari=<?php echo urlencode($tgl_dari); ?>&tgl_sampai=<?php echo urlencode($tgl_sampai); ?>&jenis=<?php echo urlencode($jenis); ?>" class="btn btn-info" target="_blank">Cetak</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-4">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <h4>Total Penjualan</h4>
                        <h2><?php echo format_rupiah($total_jual); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel panel-warning">
                    <div class="panel-body">
                        <h4>Total Modal</h4>
                        <h2><?php echo format_rupiah($total_modal); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel panel-<?php echo $total_laba >= 0 ? 'success' : 'danger'; ?>">
                    <div class="panel-body">
                        <h4>Laba Kotor</h4>
                        <h2><?php echo format_rupiah($total_laba); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Detail Transaksi</div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="tabel-laba">
                                <thead>
                                    <tr>
                                        <th width="1%">No</th>
                                        <th>No. Nota</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th class="text-right">Total Jual</th>
                                        <th class="text-right">Modal</th>
                                        <th class="text-right text-success"><strong>Laba</strong></th>
                                        <th class="text-center">Metode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    foreach($data as $d) {
                                        $labaClass = $d['laba_kotor'] >= 0 ? 'text-success' : 'text-danger';
                                        echo '<tr>';
                                        echo '<td class="text-center">'.$i++.'</td>';
                                        echo '<td><strong>'.htmlspecialchars($d['id_trx']).'</strong></td>';
                                        echo '<td>'.format_tanggal($d['tgl_trx']).'</td>';
                                        echo '<td>'.htmlspecialchars($d['nama_kon'] ?: 'Umum').'</td>';
                                        echo '<td class="text-right">'.format_rupiah($d['subtotal_jual']).'</td>';
                                        echo '<td class="text-right">'.format_rupiah($d['modal_total']).'</td>';
                                        echo '<td class="text-right '.$labaClass.'"><strong>'.format_rupiah($d['laba_kotor']).'</strong></td>';
                                        echo '<td class="text-center"><span class="label label-info">'.$d['metode_bayar'].'</span></td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr class="info">
                                        <th colspan="4" class="text-right">TOTAL</th>
                                        <th class="text-right"><?php echo format_rupiah($total_jual); ?></th>
                                        <th class="text-right"><?php echo format_rupiah($total_modal); ?></th>
                                        <th class="text-right"><strong><?php echo format_rupiah($total_laba); ?></strong></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#tabel-laba').DataTable({
        "responsive": true,
        "order": [[2, 'desc']],
        "pageLength": 25,
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }
    });
});
</script>

<?php include("layout_bottom.php"); ?>