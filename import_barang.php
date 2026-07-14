<?php
	include("sess_check.php");

	$pagedesc = "Import Barang";
	$menuparent = "import";
	include("layout_top.php");

	// ---- helper ----
	function inp_str($conn,$v){ return mysqli_real_escape_string($conn, trim(strip_tags($v))); }

	// =================================================================
	// AKSI: IMPORT CSV
	// =================================================================
	$import_result = '';
	if(isset($_POST['import_csv']) && isset($_FILES['file'])){
		$id_adm = $sess_admid;
		$success = 0;
		$errors = 0;

		$h = fopen($_FILES['file']['tmp_name'], 'r');
		if($h){
			// skip header baris pertama
			$header = fgetcsv($h, 0, ',');

			while(($row = fgetcsv($h, 0, ',')) !== false){
				if(count($row) < 4) continue; // minimal 4 kolom

				$nama    = inp_str($conn, $row[0]);
				$jenis   = !empty($row[1]) && strtolower(trim($row[1])) == 'jasa' ? 'jasa' : 'barang';
				$stok    = intval($row[2] ?? 0);
				$harga   = intval($row[3] ?? 0);
				$modal   = intval($row[4] ?? 0);
				$stok_min= intval($row[5] ?? 5);
				$keterangan = isset($row[6]) ? inp_str($conn, $row[6]) : '';

				if(empty($nama)) continue;

				$sql = "INSERT INTO barangjasa(nama, jenis, stok, harga, harga_modal, stok_min, keterangan, id_adm)
						VALUES('$nama','$jenis','$stok','$harga','$modal','$stok_min','$keterangan','$id_adm')";

				if(mysqli_query($conn, $sql)){
					$success++;
				}else{
					$errors++;
				}
			}
			fclose($h);
		}

		$import_result = "<div class='alert alert-info'>Import selesai: <strong>$success</strong> berhasil, <strong>$errors</strong> gagal.</div>";
	}
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row"><div class="col-lg-12"><h1 class="page-header">Import Barang / Jasa dari CSV</h1></div></div>
    <div class="row"><div class="col-lg-12"><?php include("layout_alert.php"); ?></div></div>

    <?php if($import_result) echo $import_result; ?>

    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-primary">
          <div class="panel-heading">Upload File CSV</div>
          <div class="panel-body">
            <form method="POST" enctype="multipart/form-data" class="form-horizontal">
              <div class="form-group">
                <label class="control-label col-sm-3">Pilih File CSV</label>
                <div class="col-sm-6">
                  <input type="file" name="file" class="form-control" accept=".csv" required>
                  <small class="text-muted">Format CSV dengan header: Nama, Jenis (barang/jasa), Stok, Harga, Harga Modal, Stok Minimum, Keterangan</small>
                </div>
              </div>
              <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                  <button type="submit" name="import_csv" class="btn btn-success">
                    <i class="fa fa-upload"></i> Import CSV
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ TEMPLATE DOWNLOAD ============ -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Template CSV</div>
          <div class="panel-body">
            <p>Download template CSV untuk diisi dengan data barang Anda:</p>
            <a href="template_barang.csv" class="btn btn-info" download>
              <i class="fa fa-download"></i> Download Template
            </a>
            <hr>
            <h4>Format Kolom:</h4>
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Kolom</th>
                    <th>Keterangan</th>
                    <th>Contoh</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>1</td><td>Nama</td><td>Nama barang / jasa</td><td>Oli Mesir 5W-30</td>
                  </tr>
                  <tr>
                    <td>2</td><td>Jenis</td><td><code>barang</code> atau <code>jasa</code></td><td>barang</td>
                  </tr>
                  <tr>
                    <td>3</td><td>Stok</td><td>Jumlah stok saat ini</td><td>50</td>
                  </tr>
                  <tr>
                    <td>4</td><td>Harga</td><td>Harga jual</td><td>85000</td>
                  </tr>
                  <tr>
                    <td>5</td><td>Harga Modal</td><td>Harga beli/modal (opsional)</td><td>65000</td>
                  </tr>
                  <tr>
                    <td>6</td><td>Stok Minimum</td><td>Batasan stok untuk alert (opsional, default 5)</td><td>10</td>
                  </tr>
                  <tr>
                    <td>7</td><td>Keterangan</td><td>Catatan tambahan (opsional)</td><td>Oli original</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include("layout_bottom.php"); ?>