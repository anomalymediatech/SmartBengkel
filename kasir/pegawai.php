<?php
include("sess_check.php");

$pagedesc = "Data Pegawai";
$menuparent = "pegawai";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Pegawai</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <a href="pegawai_tambah.php" class="btn btn-success">Tambah Pegawai</a>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-hover" id="tabel-pegawai">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th class="text-center">Telepon</th>
                                    <th class="text-right">Gaji Pokok</th>
                                    <th class="text-right">Tarif Lembur</th>
                                    <th class="text-center">Status</th>
                                    <th width="12%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $sql = "SELECT * FROM pegawai ORDER BY nama ASC";
                                $res = mysqli_query($conn, $sql);
                                while($p = mysqli_fetch_array($res)) {
                                    $statusLabel = $p['status'] == 'Aktif' ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Nonaktif</span>';
                                    echo '<tr>';
                                    echo '<td class="text-center">'.$i++.'</td>';
                                    echo '<td>'.htmlspecialchars($p['nama']).'</td>';
                                    echo '<td>'.htmlspecialchars($p['jabatan']).'</td>';
                                    echo '<td class="text-center">'.htmlspecialchars($p['wa']).'</td>';
                                    echo '<td class="text-right">'.format_rupiah($p['gaji_pokok']).'</td>';
                                    echo '<td class="text-right">'.format_rupiah($p['tarif_lembur']).'</td>';
                                    echo '<td class="text-center">'.$statusLabel.'</td>';
                                    echo '<td class="text-center">';
                                    echo '<a href="pegawai_edit.php?id='.$p['id_pegawai'].'" class="btn btn-warning btn-xs" title="Edit"><i class="fa fa-edit"></i></a> ';
                                    echo '<a href="slip_gaji_tambah.php?id_pegawai='.$p['id_pegawai'].'" class="btn btn-primary btn-xs" title="Buat Slip Gaji"><i class="fa fa-money"></i> Slip</a> ';
                                    echo '<a href="pegawai_hapus.php?id='.$p['id_pegawai'].'" onclick="return confirm(\'Hapus pegawai ini?\')" class="btn btn-danger btn-xs" title="Hapus"><i class="fa fa-trash"></i></a>';
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
    $('#tabel-pegawai').DataTable({
        "responsive": true,
        "pageLength": 25,
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Indonesian.json" }
    });
});
</script>

<?php include("layout_bottom.php"); ?>