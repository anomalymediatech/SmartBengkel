<?php
    include("sess_check.php");
    include("../dist/function/format_rupiah.php");

    // Ambil id kasir dari sesi (key: 'kasir' sesuai login_auth_v2.php)
    $id_kasir = (int)$_SESSION['kasir'];

    // Query: transaksi + omset kasir hari ini
    $sql_kasir = "SELECT COUNT(id_trx) AS jml_trx, COALESCE(SUM(total),0) AS omset
                  FROM trx
                  WHERE tgl_trx = CURDATE() AND id_kasir = $id_kasir AND status_bayar != 'Batal'";
    $res_kasir = mysqli_query($conn, $sql_kasir);
    $jml_trx   = 0;
    $omset     = 0;
    if ($res_kasir) {
        $row_kasir = mysqli_fetch_assoc($res_kasir);
        $jml_trx   = (int)$row_kasir['jml_trx'];
        $omset     = (float)$row_kasir['omset'];
    }

    // Query: item terjual kasir hari ini
    $sql_items_kasir = "SELECT COALESCE(SUM(td.jml),0) AS item_terjual
                        FROM trx_detail td JOIN trx t ON td.id_trx = t.id_trx
                        WHERE t.tgl_trx = CURDATE() AND t.id_kasir = $id_kasir AND t.status_bayar != 'Batal'";
    $res_items    = mysqli_query($conn, $sql_items_kasir);
    $item_terjual = 0;
    if ($res_items) {
        $row_items    = mysqli_fetch_assoc($res_items);
        $item_terjual = (int)$row_items['item_terjual'];
    }

$pagedesc = "Beranda";
include("layout_top.php");
?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Beranda Kasir</h1>
            </div>
        </div>

        <!-- Alert Jatuh Tempo -->
        <?php include("alert_jatuh_tempo.php"); ?>

        <!-- 3 Widget Statistik (col-lg-4 col-md-6) -->
        <div class="row">
            <!-- Widget 1: Transaksi Hari Ini -->
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-shopping-cart fa-3x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $jml_trx; ?></div>
                                <div>Transaksi Hari Ini</div>
                            </div>
                        </div>
                    </div>
                    <a href="trx.php">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Rincian</span>
                            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Widget 2: Item Terjual -->
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-green">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-cubes fa-3x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $item_terjual; ?></div>
                                <div>Item Terjual</div>
                            </div>
                        </div>
                    </div>
                    <a href="trx.php">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Rincian</span>
                            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Widget 3: Omset Saya Hari Ini -->
            <div class="col-lg-4 col-md-6">
                <div class="panel panel-yellow">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-money fa-3x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo format_rupiah($omset); ?></div>
                                <div>Omset Saya Hari Ini</div>
                            </div>
                        </div>
                    </div>
                    <a href="trx.php">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Rincian</span>
                            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
        </div><!-- /.row widget -->

        <!-- Panel Grafik Transaksi Bulan Ini -->
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-bar-chart fa-fw"></i> Grafik Transaksi Bulan Ini
                    </div>
                    <div class="panel-body">
                        <div id="chart-container">
                            <canvas id="kasirChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function(){
    $.getJSON("dashboard_data.php")
    .done(function(json){
        var ctx = document.getElementById('kasirChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: json.labels,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: json.data,
                    backgroundColor: 'rgba(92,184,92,0.6)',
                    borderColor: '#5cb85c',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    })
    .fail(function(){
        $('#chart-container').html('<p class="text-danger text-center"><i class="fa fa-exclamation-triangle"></i> Data grafik tidak dapat dimuat.</p>');
    });
});
</script>

<?php include("layout_bottom.php"); ?>
