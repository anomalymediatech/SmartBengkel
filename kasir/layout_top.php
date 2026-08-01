<?php
	// setting tanggal
	$haries = array("Sunday" => "Minggu", "Monday" => "Senin", "Tuesday" => "Selasa", "Wednesday" => "Rabu", "Thursday" => "Kamis", "Friday" => "Jum'at", "Saturday" => "Sabtu");
	$bulans = array("", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
	$bulans_count = count($bulans);
	// tanggal bulan dan tahun hari ini
	$hari_ini = $haries[date("l")];
	$bulan_ini = $bulans[date("n")];
	$tanggal = date("d");
	$bulan = date("m");
	$tahun = date("Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">

	<title>Sistem Informasi Bengkel - <?php echo $pagedesc ?></title>

	<link href="foto/logo.png" rel="icon" type="images/x-icon">

    <!-- Bootstrap Core CSS -->
	<link href="libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<!-- MetisMenu CSS -->
	<link href="libs/metisMenu/dist/metisMenu.min.css" rel="stylesheet">
	
	<!-- DataTables CSS -->
    <link href="libs/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet">

    <!-- DataTables Responsive CSS -->
    <link href="libs/datatables-responsive/css/dataTables.responsive.css" rel="stylesheet">

	<!-- Custom CSS -->
	<link href="dist/css/sb-admin-2.css" rel="stylesheet">
	<link href="dist/css/offline-font.css" rel="stylesheet">
	<link href="dist/css/custom.css" rel="stylesheet">

	<!-- Custom Fonts -->
	<link href="libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
	
	<!-- jQuery -->
	<script src="libs/jquery/dist/jquery.min.js"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <div id="wrapper">

        <!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
				<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand hidden-xs" href="index.php">
					<img src="foto/logo.png" alt="brand" width="32" class="float-left image-brand">
					<div class="float-right">&nbsp;<strong>Bengkel</strong></div>
					<div class="clear-both"></div>
				</a>
				<a class="navbar-brand visible-xs" href="index.php">
					<img src="foto/logo.png" alt="brand" width="32" class="float-left image-brand">
					<div class="float-right">&nbsp;<strong>Bengkel</strong></div>
					<div class="clear-both"></div>
				</a>
			</div><!-- /.navbar-header -->

            <ul class="nav navbar-top-links navbar-right">
				<li class="dropdown dropdown-right">
					<a class="dropdown-toggle" data-toggle="dropdown" href="#">
						<img src="../foto/<?php echo $sess_kasirfoto;?>" width="20px" height="20px" padding="0px">&nbsp;&nbsp;<?php echo ucfirst($sess_kasirname); ?>&nbsp;<i class="fa fa-caret-down"></i>
					</a>
					<ul class="dropdown-menu dropdown-user">
						<li><a href="pengaturan.php"><i class="fa fa-gear fa-fw"></i>&nbsp;Pengaturan Akun</a></li>
						<li class="divider"></li>
						<li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Keluar</a></li>
					</ul><!-- /.dropdown-user -->
				</li><!-- /.dropdown -->
			</ul><!-- /.navbar-top-links -->

            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li class="sidebar-search">
							<h4>Sistem Informasi Bengkel</h4>
							<h5 class="text-muted"><i class="fa fa-calendar fa-fw"></i>&nbsp;<?php echo $hari_ini.", ".$tanggal." ".$bulan_ini." ".$tahun ?></h5>
						</li>
						<?php
							if($pagedesc == "Beranda") {
								echo '<li><a href="index.php" class="active"><i class="fa fa-home fa-fw"></i>&nbsp;Beranda</a></li>';
							}
							else {
								echo '<li><a href="index.php"><i class="fa fa-home fa-fw"></i>&nbsp;Beranda</a></li>';
							}
							if(isset($menuparent) && $menuparent == "transaksi") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- open <li> tag generated with php, see line 134-139 -->
							<a href="#"><i class="fa fa-gear fa-fw"></i>&nbsp;Transaksi<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Transaksi Baru") {
										echo '<li><a href="trx_baru.php" class="active">Transaksi Baru</a></li>';
									}
									else {
										echo '<li><a href="trx_baru.php">Transaksi Baru</a></li>';
									}
								?>
								<?php
									if($pagedesc == "Data Transaksi") {
										echo '<li><a href="trx.php" class="active">Data Transaksi</a></li>';
									}
									else {
										echo '<li><a href="trx.php">Data Transaksi</a></li>';
									}
								?>
							</ul><!-- /.nav-second-level -->
						</li>
						<?php
							if(isset($menuparent) && $menuparent == "konsumen") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- open <li> tag generated with php, see line 155-160 -->
							<a href="#"><i class="fa fa-group fa-fw"></i>&nbsp;Data Konsumen<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Data Konsumen") {
										echo '<li><a href="konsumen.php" class="active">Data Konsumen</a></li>';
									}
									else {
										echo '<li><a href="konsumen.php">Data Konsumen</a></li>';
									}
									if($pagedesc == "Data Kendaraan") {
										echo '<li><a href="kendaraan.php" class="active">Data Kendaraan</a></li>';
									}
									else {
										echo '<li><a href="kendaraan.php">Data Kendaraan</a></li>';
									}
								?>
							</ul><!-- /.nav-second-level -->
						</li>
						<?php
							if(isset($menuparent) && $menuparent == "piutang") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- Piutang / Hutang -->
							<a href="#"><i class="fa fa-money fa-fw"></i>&nbsp;Piutang & Hutang<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Piutang Pelanggan") {
										echo '<li><a href="piutang.php" class="active">Piutang Pelanggan</a></li>';
									}
									else {
										echo '<li><a href="piutang.php">Piutang Pelanggan</a></li>';
									}
									if($pagedesc == "Hutang Supplier") {
										echo '<li><a href="hutang.php" class="active">Hutang Supplier</a></li>';
									}
									else {
										echo '<li><a href="hutang.php">Hutang Supplier</a></li>';
									}
								?>
							</ul><!-- /.nav-second-level -->
						</li>
						<?php
							if(isset($menuparent) && $menuparent == "barang") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- Data Master -->
							<a href="#"><i class="fa fa-database fa-fw"></i>&nbsp;Data Master<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Data Barang") {
										echo '<li><a href="barang.php" class="active">Data Barang</a></li>';
									}
									else {
										echo '<li><a href="barang.php">Data Barang</a></li>';
									}
									if($pagedesc == "Data Jasa") {
										echo '<li><a href="jasa.php" class="active">Data Jasa</a></li>';
									}
									else {
										echo '<li><a href="jasa.php">Data Jasa</a></li>';
									}
									if($pagedesc == "Data Supplier") {
										echo '<li><a href="supplier.php" class="active">Data Supplier</a></li>';
									}
									else {
										echo '<li><a href="supplier.php">Data Supplier</a></li>';
									}
								?>
							</ul><!-- /.nav-second-level -->
						</li>
						<?php
							if(isset($menuparent) && $menuparent == "sdm") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- SDM / Penggajian -->
							<a href="#"><i class="fa fa-users fa-fw"></i>&nbsp;SDM & Penggajian<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Data Pegawai") {
										echo '<li><a href="pegawai.php" class="active">Data Pegawai</a></li>';
									}
									else {
										echo '<li><a href="pegawai.php">Data Pegawai</a></li>';
									}
									if($pagedesc == "Slip Gaji") {
										echo '<li><a href="slip_gaji.php" class="active">Slip Gaji</a></li>';
									}
									else {
										echo '<li><a href="slip_gaji.php">Slip Gaji</a></li>';
									}
								?>
							</ul><!-- /.nav-second-level -->
						</li>
						<?php
							if(isset($menuparent) && $menuparent == "laporan") {
								echo '<li class="active">';
							}
							else {
								echo '<li>';
							}
						?>
                        <!-- Laporan -->
							<a href="#"><i class="fa fa-folder fa-fw"></i>&nbsp;Laporan<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
								<?php
									if($pagedesc == "Laporan") {
										echo '<li><a href="laporan.php" class="active">Laporan</a></li>';
									}
									else {
										echo '<li><a href="laporan.php">Laporan</a></li>';
									}
								?>
							</ul><!-- /.nav-second-level -->
						</li>
		                </ul>
                 </div>
                 <!-- /.sidebar-collapse -->
             </div>
             <!-- /.navbar-static-side -->
         </nav>