<?php
include("sess_check.php");

$pagedesc = "Hutang Supplier";
$menuparent = "hutang";
include("layout_top.php");
include("dist/function/format_rupiah.php");
include("dist/function/format_tanggal.php");
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Hutang ke Supplier</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <!-- Form Tambah Hutang -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-primary">
                    <div class="panel-heading"><h3>Tambah Hutang Supplier</h3></div>
                    <div class="panel-body">
                        <form action="hutang_insert.php" method="POST" class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Supplier</label>
                                <div class="col-sm-4">
                                    <select name="id_spl" class="form-control" required>
                                        <option value="">== Pilih Supplier ==</option>
                                        <?php
                                        $sql = "SELECT id_spl, nama_spl FROM supplier ORDER BY nama_spl ASC";
                                        $res = mysqli_query($conn, $sql);
                                        while($s = mysqli_fetch_array($res)) {
                                            echo '<option value="'.$s['id_spl'].'">'.htmlspecialchars($s['nama_spl']).'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal</label>
                                <div class="col-sm-4">
                                    <input type="date" name="tgl" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Keterangan</label>
                                <div class="col-sm-4">
                                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Pembelian sparepart" maxlength="150" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Total Hutang</label>
                                <div class="col-sm-4">
                                    <input type="number" name="total" class="form-control" min="1" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Jatuh Tempo</label>
                                <div class="col-sm-4">
                                    <input type="date" name="jatuh_tempo" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-4">
                                    <button type="submit" name="simpan" class="btn btn-success">Simpan Hutang</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Hutang -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Daftar Hutang Supplier</div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-hover" id="tabel-hutang">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th>Supplier</th>
                                    <th class="text-center">Tanggal</th>
                                    <th>Keterangan</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Terbayar</th>
                                    <th class="text-right text-danger"><strong>Sisa</strong></th>
                                    <th class="text-center">J. Tempo</th>
                                    <th class="text-center">Status</th>
                                    <th width="15%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $sql = "SELECT h.*, s.nama_spl,
                                        (SELECT SUM(jumlah) FROM bayar_hutang WHERE id_hutang=h.id_hutang) as terbayar
                                        FROM hutang_supplier h
                                        JOIN supplier s ON h.id_spl=s.id_spl
                                        ORDER BY h.tgl DESC";
                                $res = mysqli_query($conn, $sql);
                                $totalHutang = 0;
                                $totalSisa = 0;
                                while($h = mysqli_fetch_array($res)) {
                                    $terbayar = (int)$h['terbayar'];
                                    $sisa = $h['total'] - $terbayar;
                                    $totalHutang += $h['total'];
                                    $totalSisa += $sisa;
                                    
                                    $statusClass = $h['status_bayar'] == 'Lunas' ? 'label-success' : 'label-warning';
                                    $jtClass = ($h['jatuh_tempo'] && $h['status_bayar']=='Belum Lunas' && strtotime($h['jatuh_tempo']) <= strtotime('+3 days')) ? 'text-danger' : '';
                                    
                                    echo '<tr>';
                                    echo '<td class="text-center">'.$i++.'</td>';
                                    echo '<td>'.htmlspecialchars($h['nama_spl']).'</td>';
                                    echo '<td class="text-center">'.format_tanggal($h['tgl']).'</td>';
                                    echo '<td>'.htmlspecialchars($h['keterangan']).'</td>';
                                    echo '<td class="text-right">'.format_rupiah($h['total']).'</td>';
                                    echo '<td class="text-right text-success">'.format_rupiah($terbayar).'</td>';
                                    echo '<td class="text-right text-danger"><strong>'.format_rupiah($sisa).'</strong></td>';
                                    echo '<td class="text-center '.$jtClass.'">'.($h['jatuh_tempo'] ? format_tanggal($h['jatuh_tempo']) : '-').'</td>';
                                    echo '<td class="text-center"><span class="label '.$statusClass.'">'.$h['status_bayar'].'</span></td>';
                                    echo '<td class="text-center">';
                                    if($h['status_bayar'] == 'Belum Lunas') {
                                        echo '<a href="hutang_bayar.php?id='.$h['id_hutang'].'" class="btn btn-success btn-xs" title="Bayar Cicilan"><i class="fa fa-money"></i> Bayar</a> ';
                                    }
                                    echo '<a href="hutang_detail.php?id='.$h['id_hutang'].'" class="btn btn-info btn-xs" title="Detail"><i class="fa fa-eye"></i></a> ';
                                    echo '<a href="hutang_hapus.php?id='.$h['id_hutang'].'" onclick="return confirm(\'Hapus hutang ini?\')" class="btn btn-danger btn-xs" title="Hapus"><i class="fa fa-trash"></i></a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr class="info">
                                    <th colspan="4" class="text-right">TOTAL</th>
                                    <th class="text-right"><?php echo format_rupiah($totalHutang); ?></th>
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

<script>
$(document).ready(function(){
    $('#tabel-hutang').DataTable({
        "responsive": true,
        "order": [[2, 'desc']],
        "pageLength": 25,
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }
    });
});
</script>

<?php include("layout_bottom.php"); ?>