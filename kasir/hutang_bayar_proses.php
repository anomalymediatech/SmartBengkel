<?php
include("sess_check.php");

if(isset($_POST['bayar'])) {
    $id_hutang = (int)$_POST['id_hutang'];
    $jumlah    = (int)$_POST['jumlah'];
    $tgl       = $_POST['tgl'];
    $sisa      = (int)$_POST['sisa'];
    
    if($jumlah <= 0 || $jumlah > $sisa) {
        header("Location: hutang_bayar.php?id=$id_hutang&msg=".urlencode('Jumlah tidak valid'));
        exit;
    }
    
    // Cek sisa aktual
    $h = mysqli_fetch_array(mysqli_query($conn, "SELECT total, status_bayar FROM hutang_supplier WHERE id_hutang=$id_hutang"));
    $terbayar = (int)mysqli_fetch_array(mysqli_query($conn, "SELECT SUM(jumlah) FROM bayar_hutang WHERE id_hutang=$id_hutang"))[0];
    $sisaAktual = $h['total'] - $terbayar;
    
    if($jumlah > $sisaAktual) {
        header("Location: hutang_bayar.php?id=$id_hutang&msg=".urlencode('Jumlah melebihi sisa aktual'));
        exit;
    }
    
    mysqli_begin_transaction($conn);
    try {
        $sql = "INSERT INTO bayar_hutang (id_hutang, tgl, jumlah) VALUES ($id_hutang, '$tgl', $jumlah)";
        if(!mysqli_query($conn, $sql)) throw new Exception("Gagal insert bayar_hutang");
        
        $newTerbayar = $terbayar + $jumlah;
        $newStatus = ($newTerbayar >= $h['total']) ? 'Lunas' : 'Belum Lunas';
        $sql = "UPDATE hutang_supplier SET status_bayar='$newStatus' WHERE id_hutang=$id_hutang";
        if(!mysqli_query($conn, $sql)) throw new Exception("Gagal update hutang_supplier");
        
        mysqli_commit($conn);
        header("Location: hutang.php?msg=".urlencode('Pembayaran hutang supplier berhasil dicatat'));
    } catch(Exception $e) {
        mysqli_rollback($conn);
        header("Location: hutang_bayar.php?id=$id_hutang&msg=".urlencode('Error: '.$e->getMessage()));
    }
    exit;
}
header("Location: hutang.php");
?>