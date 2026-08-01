<?php
include("sess_check.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id == 0) { header("Location: pegawai.php"); exit; }

$sql = "SELECT * FROM pegawai WHERE id_pegawai=$id";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0) { header("Location: pegawai.php"); exit; }
$p = mysqli_fetch_array($res);

$pagedesc = "Edit Pegawai";
$menuparent = "pegawai";
include("layout_top.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Edit Pegawai</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3>Edit Data Pegawai</h3></div>
                    <div class="panel-body">
                        <form action="pegawai_update.php" method="POST" class="form-horizontal">
                            <input type="hidden" name="id" value="<?php echo $p['id_pegawai']; ?>">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input type="text" name="nama" class="form-control" placeholder="Nama lengkap" value="<?php echo htmlspecialchars($p['nama']); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Jabatan</label>
                                <div class="col-sm-6">
                                    <select name="jabatan" class="form-control">
                                        <option value="Mekanik" <?php echo $p['jabatan']=='Mekanik'?'selected':''; ?>>Mekanik</option>
                                        <option value="Kasir" <?php echo $p['jabatan']=='Kasir'?'selected':''; ?>>Kasir</option>
                                        <option value="Admin" <?php echo $p['jabatan']=='Admin'?'selected':''; ?>>Admin</option>
                                        <option value="Lainnya" <?php echo $p['jabatan']=='Lainnya'?'selected':''; ?>>Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">WhatsApp</label>
                                <div class="col-sm-6">
                                    <input type="text" name="wa" class="form-control" placeholder="08123456789" maxlength="20" value="<?php echo htmlspecialchars($p['wa']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Gaji Pokok (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="number" name="gaji_pokok" class="form-control" min="0" value="<?php echo $p['gaji_pokok']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tarif Lembur/jam (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="number" name="tarif_lembur" class="form-control" min="0" value="<?php echo $p['tarif_lembur']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Status</label>
                                <div class="col-sm-6">
                                    <select name="status" class="form-control">
                                        <option value="Aktif" <?php echo $p['status']=='Aktif'?'selected':''; ?>>Aktif</option>
                                        <option value="Nonaktif" <?php echo $p['status']=='Nonaktif'?'selected':''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-6">
                                    <button type="submit" name="perbarui" class="btn btn-success">Update</button>
                                    <a href="pegawai.php" class="btn btn-default">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("layout_bottom.php"); ?>