<?php
	include("sess_check.php");
	
	// deskripsi halaman
	$pagedesc = "Import Barang/Jasa dari CSV";
	$menuparent = "barang";
	include("layout_top.php");
?>
<!-- top of file -->
		<!-- Page Content -->
		<div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Import Barang/Jasa dari CSV</h1>
                    </div><!-- /.col-lg-12 -->
                </div><!-- /.row -->

				<div class="row">
					<div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
				</div>
				
				<div class="row">
					<div class="col-lg-8">
						<!-- Panduan Format -->
						<div class="panel panel-info">
							<div class="panel-heading"><h3>Format CSV yang Didukung</h3></div>
							<div class="panel-body">
								<p>File CSV harus memiliki header kolom berikut (urutan bebas):</p>
								<table class="table table-bordered">
									<thead>
										<tr class="info">
											<th>Kolom</th>
											<th>Wajib</th>
											<th>Keterangan</th>
										</tr>
									</thead>
									<tbody>
										<tr><td><code>Nama</code></td><td>Ya</td><td>Nama barang/jasa</td></tr>
										<tr><td><code>Jenis</code></td><td>Ya</td><td><code>barang</code> atau <code>jasa</code></td></tr>
										<tr><td><code>Stok</code></td><td>Tidak</td><td>Angka (default: 0). Untuk jasa isi 0.</td></tr>
										<tr><td><code>Harga</code></td><td>Ya</td><td>Harga jual (angka)</td></tr>
										<tr><td><code>Harga Modal</code></td><td>Tidak</td><td>Harga beli/modal (default: 0)</td></tr>
										<tr><td><code>Stok Minimum</code></td><td>Tidak</td><td>Untuk alert stok menipis (default: 5)</td></tr>
										<tr><td><code>Keterangan</code></td><td>Tidak</td><td>Deskripsi tambahan</td></tr>
									</tbody>
								</table>
								<p>Contoh baris data:</p>
								<pre><code>Oli Mesin 5W-30,barang,50,85000,65000,10,Oli original
Busi Iridium,barang,30,45000,35000,5,
Ganti Oli,jasa,0,50000,0,0,Termasuk jasa ganti</code></pre>
								<div class="alert alert-warning">
									<strong>Catatan:</strong> 
									<ul>
										<li>Encoding file: UTF-8 (disarankan)</li>
										<li>Pemisah: koma (,) - format CSV standar</li>
										<li>Jika nama mengandung koma, bungkus dengan kutip ganda: <code>"Nama, Barang"</code></li>
										<li>Data yang error akan dilewati, pesan error ditampilkan di bawah</li>
									</ul>
								</div>
							</div>
						</div>
						
						<!-- Download Template -->
						<div class="panel panel-default">
							<div class="panel-heading"><h3>Download Template</h3></div>
							<div class="panel-body">
								<a href="../template_barang.csv" class="btn btn-primary" download>Download template_barang.csv</a>
							</div>
						</div>
						
						<!-- Form Upload -->
						<div class="panel panel-primary">
							<div class="panel-heading"><h3>Upload File CSV</h3></div>
							<div class="panel-body">
								<form action="barang_import_proses.php" method="POST" enctype="multipart/form-data">
									<div class="form-group">
										<label>File CSV</label>
										<input type="file" name="file_csv" class="form-control" accept=".csv" required>
										<small class="text-muted">Maksimal 2MB</small>
									</div>
									<div class="form-group">
										<label class="checkbox-inline">
											<input type="checkbox" name="skip_duplicate" value="1" checked> Lewati barang yang sudah ada (berdasarkan nama)
										</label>
									</div>
									<button type="submit" name="import" class="btn btn-success">Import</button>
									<a href="barang.php" class="btn btn-default">Batal</a>
								</form>
							</div>
						</div>
					</div>
					
					<!-- Sidebar Info -->
					<div class="col-lg-4">
						<div class="panel panel-default">
							<div class="panel-heading"><h3>Status Import Sebelumnya</h3></div>
							<div class="panel-body">
								<?php
								$log_file = '../logs/import_barang.log';
								if(file_exists($log_file)) {
									$lines = file($log_file);
									$lines = array_reverse($lines);
									$count = min(10, count($lines));
									echo '<ul class="list-unstyled">';
									for($i=0; $i<$count; $i++) {
										echo '<li style="border-bottom:1px solid #eee; padding:5px 0;">'.htmlspecialchars($lines[$i]).'</li>';
									}
									echo '</ul>';
								} else {
									echo '<p class="text-muted">Belum ada riwayat import.</p>';
								}
								?>
							</div>
						</div>
					</div>
				</div>
            </div>
        </div>
<?php include("layout_bottom.php"); ?>