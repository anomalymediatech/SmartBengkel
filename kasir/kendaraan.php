<?php
include("sess_check.php");

$pagedesc = "Data Kendaraan Pelanggan";
$menuparent = "kendaraan";
include("layout_top.php");
include("dist/function/format_tanggal.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Data Kendaraan Pelanggan</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <a href="kendaraan_tambah.php" class="btn btn-success">Tambah Kendaraan</a>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered table-hover" id="tabel-data">
                            <thead>
                                <tr>
                                    <th width="1%">No</th>
                                    <th width="10%">Pelanggan</th>
                                    <th width="10%">Plat Nomor</th>
                                    <th width="8%">Merk</th>
                                    <th width="8%">Tipe</th>
                                    <th width="5%">Tahun</th>
                                    <th width="10%">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i = 1;
                                    $sql = "SELECT k.*, ko.nama_kon FROM kendaraan k
                                            LEFT JOIN konsumen ko ON k.id_kon = ko.id_kon
                                            ORDER BY ko.nama_kon ASC, k.plat_nomor ASC";
                                    $ress = mysqli_query($conn, $sql);
                                    while($data = mysqli_fetch_array($ress)) {
                                        echo '<tr>';
                                        echo '<td class="text-center">'. $i .'</td>';
                                        echo '<td>'. htmlspecialchars($data['nama_kon']) .'</td>';
                                        echo '<td class="text-center"><strong>'. htmlspecialchars($data['plat_nomor']) .'</strong></td>';
                                        echo '<td>'. htmlspecialchars($data['merk']) .'</td>';
                                        echo '<td>'. htmlspecialchars($data['tipe']) .'</td>';
                                        echo '<td class="text-center">'. htmlspecialchars($data['tahun']) .'</td>';
                                        echo '<td class="text-center">
                                                <a href="kendaraan_edit.php?id='. $data['id_kendaraan'] .'" class="btn btn-warning btn-xs">Edit</a>
                                                <a href="kendaraan_hapus.php?id='. $data['id_kendaraan'] .'" onclick="return confirm(\'Apakah anda yakin akan menghapus kendaraan ini?\')" class="btn btn-danger btn-xs">Hapus</a>
                                              </td>';
                                        echo '</tr>';
                                        $i++;
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

<script type="text/javascript">
$(document).ready(function() {
    $('#tabel-data').DataTable({
        "responsive": true,
        "processing": true,
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });
    
    $('#tabel-data').parent().addClass("table-responsive");
});
</script>

<?php include("layout_bottom.php"); ?>