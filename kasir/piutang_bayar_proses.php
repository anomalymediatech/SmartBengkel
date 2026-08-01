<?php
include("sess_check.php");

if(isset($_POST['bayar'])) {
    $id_trx = mysqli_real_escape_string($conn, $_POST['id_trx']);
    $jumlah = (int)$_POST['jumlah'];
    $tgl    = $_POST['tgl'] ?? date('Y-m-d');
    $id_kasir = $sess_kasirid;
    
    if($jumlah <= 0) {
        echo "<script>alert('Jumlah harus > 0'); window.history.back();</script>";
        exit;
    }
    
    // Cek sisa piutang
    $sql = "SELECT trx.*, 
                (trx.total - trx.bayar - IFNULL((SELECT SUM(jumlah) FROM bayar_piutang WHERE id_trx=trx.id_trx),0)) as sisa
            FROM trx WHERE id_trx='$id_trx'";
    $res = mysqli_query($conn, $sql);
    $trx = mysqli_fetch_array($res);
    
    if($jumlah > $trx['sisa']) {
        header("Location: piutang_bayar.php?id=$id_trx&msg=".urlencode('Jumlah melebihi sisa piutang'));
        exit;
    }
    
    mysqli_begin_transaction($conn);
    try {
        $sql = "INSERT INTO bayar_piutang (id_trx, tgl, jumlah, id_kasir) VALUES ('$id_trx', '$tgl', $jumlah, $id_kasir)";
        if(!mysqli_query($conn, $sql)) throw new Exception("Gagal insert bayar_piutang");
        
        $total_bayar = $trx['bayar'] + $jumlah;
        $new_status = ($total_bayar >= $trx['total']) ? 'Lunas' : 'Belum Lunas';
        $sql = "UPDATE trx SET bayar=$total_bayar, status_bayar='$new_status' WHERE id_trx='$id_trx'";
        if(!mysqli_query($conn, $sql)) throw new Exception("Gagal update trx");
        
        mysqli_commit($conn);
        header("Location: piutang.php?msg=".urlencode('Cicilan piutang berhasil dicatat'));
    } catch(Exception $e) {
        mysqli_rollback($conn);
        header("Location: piutang_bayar.php?id=$id_trx&msg=".urlencode('Error: '.$e->getMessage()));
    }
    exit;
}
header("Location: piutang.php");
?>