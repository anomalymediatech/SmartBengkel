<?php
include("sess_check.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id == 0) {
    header("Location: kendaraan.php");
    exit;
}

$sql = "SELECT * FROM kendaraan WHERE id_kendaraan=$id";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) {
    header("Location: kendaraan.php");
    exit;
}
$data = mysqli_fetch_array($res);

$pagedesc = "Edit Kendaraan";
$menuparent = "kendaraan";
include("layout_top.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Edit Kendaraan Pelanggan</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="kendaraan_update.php" method="POST">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3>Edit Kendaraan</h3></div>
                        <div class="panel-body">
                            <input type="hidden" name="id" value="<?php echo $data['id_kendaraan']; ?>">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Pelanggan <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <select name="id_kon" class="form-control" required>
                                        <option value="">== Pilih Pelanggan ==</option>
                                        <?php
                                        $sql = "SELECT id_kon, nama_kon, plat_nomor FROM konsumen WHERE id_kon!='0' ORDER BY nama_kon ASC";
                                        $res = mysqli_query($conn, $sql);
                                        while($k = mysqli_fetch_array($res)) {
                                            $sel = ($k['id_kon'] == $data['id_kon']) ? 'selected' : '';
                                            $plat = $k['plat_nomor'] ? " ({$k['plat_nomor']})" : '';
                                            echo '<option value="'.$k['id_kon'].'" '.$sel.'>'.htmlspecialchars($k['nama_kon']).$plat.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Plat Nomor <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input type="text" name="plat_nomor" class="form-control" placeholder="Contoh: B 1234 ABC" maxlength="15" value="<?php echo htmlspecialchars($data['plat_nomor']); ?>" required style="text-transform:uppercase">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Merk</label>
                                <div class="col-sm-4">
                                    <input type="text" name="merk" class="form-control" placeholder="Contoh: Honda, Yamaha, Suzuki" maxlength="50" value="<?php echo htmlspecialchars($data['merk']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tipe</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tipe" class="form-control" placeholder="Contoh: Beat, Vario, NMAX" maxlength="50" value="<?php echo htmlspecialchars($data['tipe']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tahun</label>
                                <div class="col-sm-4">
                                    <input type="text" name="tahun" class="form-control" placeholder="Contoh: 2022" maxlength="4" pattern="[0-9]{4}" value="<?php echo htmlspecialchars($data['tahun']); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" name="perbarui" class="btn btn-success">Update</button>
                            <a href="kendaraan.php" class="btn btn-default">Kembali</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include("layout_bottom.php"); ?>