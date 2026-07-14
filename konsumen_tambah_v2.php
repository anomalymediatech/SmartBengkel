<?php
	include("sess_check.php");

	// deskripsi halaman
	$pagedesc = "Data Konsumen";
	$menuparent = "konsumen";
	include("layout_top.php");
?>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Tambah Data Konsumen (v2)</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>

				<div class="row">
					<div class="col-lg-12">
						<form class="form-horizontal" action="konsumen_insert_v2.php" method="POST">
							<div class="panel panel-default">
								<div class="panel-heading"><h3>Tambah Data</h3></div>
								<div class="panel-body">
									<div class="form-group">
										<label class="control-label col-sm-3">Nama</label>
										<div class="col-sm-4">
											<input type="text" name="nama" class="form-control" placeholder="Nama" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Telepon</label>
										<div class="col-sm-4">
											<input type="number" name="telp" min="0" class="form-control" placeholder="Telepon" required>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Nomor WhatsApp</label>
										<div class="col-sm-4">
											<input type="text" name="wa_kon" class="form-control" placeholder="62812xxxxxxx" maxlength="20">
											<small class="text-muted">Gunakan format internasional (mulai 62)</small>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Plat Nomor Kendaraan</label>
										<div class="col-sm-4">
											<input type="text" name="plat_nomor" class="form-control" placeholder="mis. B 1234 XYZ" maxlength="15">
											<small class="text-muted">Plat nomor kendaraan utama pelanggan ini</small>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-3">Alamat</label>
										<div class="col-sm-4">
											<textarea name="alamat" class="form-control" placeholder="Alamat" rows="3" required></textarea>
										</div>
									</div>
								</div>
								<div class="panel-footer">
									<button type="submit" name="simpan" class="btn btn-success">Simpan</button>
									<a href="konsumen.php" class="btn btn-default">Kembali</a>
								</div>
							</div><!-- /.panel -->
						</form>
					</div><!-- /.col-lg-12 -->
				</div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div><!-- /#page-wrapper -->
<!-- bottom of file -->
<?php
	include("layout_bottom.php");
?>
