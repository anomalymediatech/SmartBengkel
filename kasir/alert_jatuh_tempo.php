<?php
// Komponen Alert Jatuh Tempo - include di dashboard
// Cek piutang pelanggan (metode bayar Hutang) yang mendekati/lewat jatuh tempo ≤ 3 hari
$al_piutang = mysqli_query($conn, "
    SELECT COUNT(*) as cnt FROM trx 
    WHERE metode_bayar='Hutang' 
    AND status_bayar='Belum Lunas' 
    AND jatuh_tempo IS NOT NULL 
    AND jatuh_tempo <= CURDATE() + INTERVAL 3 DAY
");
$n_piutang = mysqli_fetch_array($al_piutang)['cnt'];

// Cek hutang supplier yang mendekati/lewat jatuh tempo ≤ 3 hari
$al_hutang = mysqli_query($conn, "
    SELECT COUNT(*) as cnt FROM hutang_supplier 
    WHERE status_bayar='Belum Lunas' 
    AND jatuh_tempo IS NOT NULL 
    AND jatuh_tempo <= CURDATE() + INTERVAL 3 DAY
");
$n_hutang = mysqli_fetch_array($al_hutang)['cnt'];

$total_alert = $n_piutang + $n_hutang;

if($total_alert > 0): ?>
<div class="alert alert-blink alert-danger" style="margin-bottom:20px; animation: blink 1.2s step-start infinite;">
    <div class="row">
        <div class="col-sm-10">
            <strong><i class="fa fa-exclamation-triangle"></i> PERHATIAN!</strong>
            <?php if($n_piutang > 0): ?>
                <span class="label label-warning" style="font-size:12px; margin:0 5px;">Piutang: <?php echo $n_piutang; ?></span>
            <?php endif; ?>
            <?php if($n_hutang > 0): ?>
                <span class="label label-danger" style="font-size:12px; margin:0 5px;">Hutang Supplier: <?php echo $n_hutang; ?></span>
            <?php endif; ?>
            mendekati / lewat jatuh tempo (≤ 3 hari).
            <a href="../piutang.php" class="btn btn-xs btn-warning" style="margin-left:10px;"><i class="fa fa-users"></i> Lihat Piutang</a>
            <a href="../hutang.php" class="btn btn-xs btn-danger" style="margin-left:5px;"><i class="fa fa-truck"></i> Lihat Hutang</a>
        </div>
        <div class="col-sm-2 text-right">
            <small class="text-muted">Update otomatis tiap reload halaman</small>
        </div>
    </div>
</div>
<style>
@keyframes blink {
    50% { opacity: 0.5; }
}
.alert-blink {
    padding: 15px;
    border-radius: 4px;
    border: none;
    font-weight: bold;
}
</style>
<?php endif; ?>