<?php
include("sess_check.php");

$pagedesc = "Piutang Pelanggan";
$menuparent = "piutang";
include("layout_top.php");
include("dist/function/format_rupiah.php");
include("dist/function/format_tanggal.php");

// Filter
$where = "1=1";
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
if($search !== '') {
    $where .= " AND (ko.nama_kon LIKE '%$search%' OR ko.telp_kon LIKE '%$search%' OR ko.wa_kon LIKE '%$search%' OR trx.plat_nomor LIKE '%$search%')";
}
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
if($statusFilter === 'belum_lunas') $where .= " AND trx.status_bayar='Belum Lunas'";
elseif($statusFilter === 'lunas') $where .= " AND trx.status_bayar='Lunas'";

// Query piutang: transaksi metode_bayar='Hutang'
$sql = "SELECT trx.*, ko.nama_kon, ko.telp_kon, ko.wa_kon,
            (trx.total - trx.bayar - COALESCE((SELECT SUM(jumlah) FROM bayar_piutang bp WHERE bp.id_trx=trx.id_trx), 0)) AS sisa_piutang
        FROM trx
        LEFT JOIN konsumen ko ON trx.id_kon=ko.id_kon
        WHERE trx.metode_bayar='Hutang' AND $where
        ORDER BY trx.tgl_trx DESC";
$res = mysqli_query($conn, $sql);
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Piutang Pelanggan (Hutang/Bon)</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Filter -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Filter Piutang</div>
                    <div class="panel-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group">
                                <label>Cari</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama/No.Telp/WA/Plat" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Semua</option>
                                    <option value="belum_lunas" <?php if($statusFilter==='belum_lunas') echo 'selected'; ?>>Belum Lunas</option>
                                    <option value="lunas" <?php if($statusFilter==='lunas') echo 'selected'; ?>>Lunas</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="piutang.php" class="btn btn-default">Reset</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Piutang -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Daftar Piutang Pelanggan</div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="tabel-piutang">
                                <thead>
                                    <tr>
                                        <th width="1%">No</th>
                                        <th>No. Nota</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>Plat</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">DP/Bayar</th>
                                        <th class="text-right text-danger"><strong>Sisa</strong></th>
                                        <th class="text-center">Status</th>
                                        <th>J. Tempo</th>
                                        <th width="12%">Opsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $totalPiutang = 0;
                                    $totalSisa = 0;
                                    if(mysqli_num_rows($res) > 0) {
                                        while($row = mysqli_fetch_array($res)) {
                                            $sisa = $row['sisa_piutang'];
                                            $totalPiutang += $row['total'];
                                            $totalSisa += $sisa;
                                            
                                            $statusLabel = $row['status_bayar'] == 'Lunas' 
                                                ? '<span class="label label-success">Lunas</span>' 
                                                : '<span class="label label-warning">Belum Lunas</span>';
                                            
                                            $jt = $row['jatuh_tempo'] ? format_tanggal($row['jatuh_tempo']) : '-';
                                            $jtClass = '';
                                            if($row['status_bayar'] == 'Belum Lunas' && $row['jatuh_tempo']) {
                                                $diff = (strtotime($row['jatuh_tempo']) - strtotime(date('Y-m-d'))) / 86400;
                                                if($diff < 0) $jtClass = 'text-danger fw-bold';
                                                elseif($diff <= 3) $jtClass = 'text-warning fw-bold';
                                            }
                                            
                                            echo '<tr>';
                                            echo '<td class="text-center">'.$i++.'</td>';
                                            echo '<td><strong>'.htmlspecialchars($row['id_trx']).'</strong></td>';
                                            echo '<td>'.format_tanggal($row['tgl_trx']).'</td>';
                                            echo '<td>'.htmlspecialchars($row['nama_kon']).'<br><small>'.htmlspecialchars($row['telp_kon']).' / '.htmlspecialchars($row['wa_kon']).'</small></td>';
                                            echo '<td class="text-center">'.htmlspecialchars($row['plat_nomor']).'</td>';
                                            echo '<td class="text-right">'.format_rupiah($row['total']).'</td>';
                                            echo '<td class="text-right">'.format_rupiah($row['bayar']).'</td>';
                                            echo '<td class="text-right '.($sisa > 0 ? 'text-danger' : '').'"><strong>'.format_rupiah($sisa).'</strong></td>';
                                            echo '<td class="text-center">'.$statusLabel.'</td>';
                                            echo '<td class="text-center '.$jtClass.'">'.$jt.'</td>';
                                            echo '<td class="text-center">';
                                            if($sisa > 0) {
                                                echo '<a href="piutang_bayar.php?id='.$row['id_trx'].'" class="btn btn-success btn-xs" title="Cicilan"><i class="fa fa-money"></i> Bayar</a> ';
                                            }
                                            echo '<a href="piutang_detail.php?id='.$row['id_trx'].'" class="btn btn-info btn-xs" title="Detail"><i class="fa fa-eye"></i></a> ';
                                            echo '<a href="../struk_thermal.php?id='.$row['id_trx'].'" target="_blank" class="btn btn-default btn-xs" title="Cetak"><i class="fa fa-print"></i></a>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="11" class="text-center text-muted">Tidak ada data piutang</td></tr>';
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr class="info">
                                        <th colspan="5" class="text-right">TOTAL</th>
                                        <th class="text-right"><?php echo format_rupiah($totalPiutang); ?></th>
                                        <th class="text-right"></th>
                                        <th class="text-right text-danger"><strong><?php echo format_rupiah($totalSisa); ?></strong></th>
                                        <th colspan="3"></th>
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
    $('#tabel-piutang').DataTable({
        "responsive": true,
        "order": [[2, 'desc']],
        "pageLength": 25,
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }
    });
});
</script>

<?php include("layout_bottom.php"); ?>