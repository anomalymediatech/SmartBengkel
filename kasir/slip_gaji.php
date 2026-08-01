<?php
include("sess_check.php");

$pagedesc = "Slip Gaji";
$menuparent = "pegawai";
include("layout_top.php");
include("dist/function/format_rupiah.php");
include("dist/function/format_tanggal.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Slip Gaji Pegawai</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <a href="slip_gaji_tambah.php" class="btn btn-success">Buat Slip Gaji Baru</a>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-hover" id="tabel-slip">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th>Pegawai</th>
                                    <th class="text-center">Periode</th>
                                    <th class="text-right">Gaji Pokok</th>
                                    <th class="text-center">Jam Lembur</th>
                                    <th class="text-right">Tarif Lembur</th>
                                    <th class="text-right">Bonus Lembur</th>
                                    <th class="text-right">Potongan</th>
                                    <th class="text-right"><strong>Gaji Bersih</strong></th>
                                    <th class="text-center">Tgl Bayar</th>
                                    <th width="12%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $sql = "SELECT s.*, p.nama, p.jabatan 
                                        FROM slip_gaji s
                                        JOIN pegawai p ON s.id_pegawai=p.id_pegawai
                                        ORDER BY s.tgl_bayar DESC, p.nama ASC";
                                $res = mysqli_query($conn, $sql);
                                while($s = mysqli_fetch_array($res)) {
                                    echo '<tr>';
                                    echo '<td class="text-center">'.$i++.'</td>';
                                    echo '<td>'.htmlspecialchars($s['nama']).' <small class="text-muted">('.$s['jabatan'].')</small></td>';
                                    echo '<td class="text-center">'.htmlspecialchars($s['periode']).'</td>';
                                    echo '<td class="text-right">'.format_rupiah($s['gaji_pokok']).'</td>';
                                    echo '<td class="text-center">'.$s['jam_lembur'].'</td>';
                                    echo '<td class="text-right">'.format_rupiah($s['tarif_lembur']).'</td>';
                                    echo '<td class="text-right text-success">'.format_rupiah($s['bonus_lembur']).'</td>';
                                    echo '<td class="text-right text-danger">'.format_rupiah($s['potongan']).'</td>';
                                    echo '<td class="text-right"><strong>'.format_rupiah($s['gaji_bersih']).'</strong></td>';
                                    echo '<td class="text-center">'.format_tanggal($s['tgl_bayar']).'</td>';
                                    echo '<td class="text-center">';
                                    echo '<a href="slip_gaji_cetak.php?id='.$s['id_slip'].'" class="btn btn-info btn-xs" title="Cetak" target="_blank"><i class="fa fa-print"></i></a> ';
                                    echo '<a href="slip_gaji_wa.php?id='.$s['id_slip'].'" class="btn btn-success btn-xs" title="Kirim WA"><i class="fa fa-whatsapp"></i></a> ';
                                    echo '<a href="slip_gaji_hapus.php?id='.$s['id_slip'].'" onclick="return confirm(\'Hapus slip gaji ini?\')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>';
                                    echo '</td>';
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

<script>
$(document).ready(function(){
    $('#tabel-slip').DataTable({
        "responsive": true,
        "order": [[9, 'desc']],
        "pageLength": 25,
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }
    });
});
</script>

<?php include("layout_bottom.php"); ?>