<?php
include("sess_check.php");

$pagedesc = "Data Kendaraan";
$menuparent = "kendaraan";
include("layout_top.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Tambah Kendaraan Pelanggan</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="kendaraan_insert.php" method="POST">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3>Tambah Kendaraan</h3></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Pelanggan <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <select name="id_kon" class="form-control" required>
                                        <option value="">== Pilih Pelanggan ==</option>
                                        <?php
                                        $sql = "SELECT id_kon, nama_kon, plat_nomor FROM konsumen WHERE id_kon!='0' ORDER BY nama_kon ASC";
                                        $res = mysqli_query($conn, $sql);
                                        while($k = mysqli_fetch_array($res)) {
                                            $plat = $k['plat_nomor'] ? " ({$k['plat_nomor']})" : '';
                                            echo '<option value="'.$k['id_kon'].'">'.htmlspecialchars($k['nama_kon']).$plat.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Plat Nomor <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="text" name="plat_nomor" class="form-control" placeholder="Contoh: B 1234 ABC" maxlength="15" required style="text-transform:uppercase">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Merk</label>
                                <div class="col-sm-4">
                                    <input type="text" name="merk" class="form-control" placeholder="Contoh: Honda, Yamaha, Suzuki" maxlength="50">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tipe</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tipe" class="form-control" placeholder="Contoh: Beat, Vario, NMAX" maxlength="50">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tahun</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tahun" class="form-control" placeholder="Contoh: 2022" maxlength="4" pattern="[0-9]{4}">
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                            <a href="kendaraan.php" class="btn btn-default">Kembali</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include("layout_bottom.php"); ?>