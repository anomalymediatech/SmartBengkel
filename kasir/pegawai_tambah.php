<?php
include("sess_check.php");

$pagedesc = "Tambah Pegawai";
$menuparent = "pegawai";
include("layout_top.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Tambah Pegawai</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3>Data Pegawai</h3></div>
                    <div class="panel-body">
                        <form action="pegawai_insert.php" method="POST" class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Nama <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input type="text" name="nama" class="form-control" placeholder="Nama lengkap" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Jabatan</label>
                                <div class="col-sm-6">
                                    <select name="jabatan" class="form-control">
                                        <option value="Mekanik">Mekanik</option>
                                        <option value="Kasir">Kasir</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">WhatsApp</label>
                                <div class="col-sm-6">
                                    <input type="text" name="wa" class="form-control" placeholder="08123456789" maxlength="20">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Gaji Pokok (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="number" name="gaji_pokok" class="form-control" min="0" value="0" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tarif Lembur/jam (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="number" name="tarif_lembur" class="form-control" min="0" value="0" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Status</label>
                                <div class="col-sm-6">
                                    <select name="status" class="form-control">
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-6">
                                    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
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