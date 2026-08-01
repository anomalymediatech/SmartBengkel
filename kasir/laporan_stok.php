<?php
include("sess_check.php");

$pagedesc = "Laporan Stok Menipis (Reorder)";
$menuparent = "laporan";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Laporan Stok Menipis / Reorder</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Summary -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Barang dengan Stok ≤ Stok Minimum</div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="tabel-stok">
                                <thead>
                                    <tr>
                                        <th width="1%">No</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center">Stok Saat Ini</th>
                                        <th class="text-center">Stok Minimum</th>
                                        <th class="text-center">Kebutuhan (Min - Stok)</th>
                                        <th class="text-right">Harga Modal</th>
                                        <th class="text-right">Est. Biaya Reorder</th>
                                        <th class="text-center">Supplier</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $total_biaya = 0;
                                    // Barang dengan stok <= stok_min
                                    $sql = "SELECT bj.* 
                                            FROM barangjasa bj
                                            WHERE bj.jenis='barang' 
                                            AND CAST(bj.stok AS UNSIGNED) <= bj.stok_min
                                            ORDER BY (bj.stok_min - CAST(bj.stok AS UNSIGNED)) DESC, bj.nama ASC";
                                    $res = mysqli_query($conn, $sql);
                                    $hasData = false;
                                    while($row = mysqli_fetch_array($res)) {
                                        $hasData = true;
                                        $stok = (int)$row['stok'];
                                        $min = (int)$row['stok_min'];
                                        $kebutuhan = $min - $stok;
                                        if($kebutuhan < 0) $kebutuhan = 0;
                                        $biaya = $kebutuhan * (int)$row['harga_modal'];
                                        $total_biaya += $biaya;
                                        
                                        $stokClass = $stok == 0 ? 'text-danger fw-bold' : ($stok < $min/2 ? 'text-warning' : '');
                                        echo '<tr>';
                                        echo '<td class="text-center">'.$i++.'</td>';
                                        echo '<td>'.htmlspecialchars($row['nama']).'</td>';
                                        echo '<td class="text-center '.$stokClass.'">'.$stok.'</td>';
                                        echo '<td class="text-center">'.$min.'</td>';
                                        echo '<td class="text-center text-danger fw-bold">'.$kebutuhan.'</td>';
                                        echo '<td class="text-right">'.format_rupiah($row['harga_modal']).'</td>';
                                        echo '<td class="text-right text-danger">'.format_rupiah($biaya).'</td>';
                                        echo '<td class="text-center">-</td>';
                                        echo '</tr>';
                                    }
                                    if(!$hasData) {
                                        echo '<tr><td colspan="8" class="text-center text-success"><strong>Tidak ada barang yang perlu di-reorder. Semua stok aman!</strong></td></tr>';
                                    }
                                    ?>
                                </tbody>
                                <?php if($hasData): ?>
                                <tfoot>
                                    <tr class="info">
                                        <th colspan="6" class="text-right">TOTAL ESTIMASI BIAYA REORDER</th>
                                        <th class="text-right text-danger"><strong><?php echo format_rupiah($total_biaya); ?></strong></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <div class="row" style="margin-top:15px;">
                            <div class="col-lg-6">
                                <a href="laporan_stok_cetak.php" class="btn btn-info" target="_blank"><i class="fa fa-print"></i> Cetak Laporan</a>
                            </div>
                            <div class="col-lg-6 text-right">
                                <a href="barang_import.php" class="btn btn-primary"><i class="fa fa-upload"></i> Import Barang (Tambah Stok)</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Semua barang untuk referensi -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Semua Barang (Referensi Stok)</div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="tabel-all-stok">
                                <thead>
                                    <tr>
                                        <th width="1%">No</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center">Stok</th>
                                        <th class="text-center">Min</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-right">Harga Modal</th>
                                        <th class="text-right">Harga Jual</th>
                                        <th class="text-center">Supplier</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $sql = "SELECT bj.*
                                            FROM barangjasa bj
                                            WHERE bj.jenis='barang'
                                            ORDER BY bj.nama ASC";
                                    $res = mysqli_query($conn, $sql);
                                    while($row = mysqli_fetch_array($res)) {
                                        $stok = (int)$row['stok'];
                                        $min = (int)$row['stok_min'];
                                        $status = $stok == 0 ? '<span class="label label-danger">HABIS</span>' : ($stok <= $min ? '<span class="label label-warning">REORDER</span>' : '<span class="label label-success">AMAN</span>');
                                        echo '<tr>';
                                        echo '<td class="text-center">'.$i++.'</td>';
                                        echo '<td>'.htmlspecialchars($row['nama']).'</td>';
                                        echo '<td class="text-center">'.$stok.'</td>';
                                        echo '<td class="text-center">'.$min.'</td>';
                                        echo '<td class="text-center">'.$status.'</td>';
                                        echo '<td class="text-right">'.format_rupiah($row['harga_modal']).'</td>';
                                        echo '<td class="text-right">'.format_rupiah($row['harga']).'</td>';
                                        echo '<td class="text-center">-</td>';
                                        echo '</tr>';
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
</div>

<script>
$(document).ready(function(){
    $('#tabel-stok').DataTable({
        "responsive": true,
        "pageLength": 25,
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }
    });
    $('#tabel-all-stok').DataTable({
        "responsive": true,
        "pageLength": 25,
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }
    });
});
</script>

<?php include("layout_bottom.php"); ?>