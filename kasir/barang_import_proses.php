<?php
include("sess_check.php");

if(isset($_POST['import']) && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv'];
    $skipDuplicate = isset($_POST['skip_duplicate']) && $_POST['skip_duplicate'] == '1';
    
    // Validasi file
    if($file['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Error upload file: ' . $file['error'];
        header("Location: barang_import.php?msg=" . urlencode($msg));
        exit;
    }
    
    if($file['size'] > 2 * 1024 * 1024) {
        header("Location: barang_import.php?msg=" . urlencode('File terlalu besar (maks 2MB)'));
        exit;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if($ext !== 'csv') {
        header("Location: barang_import.php?msg=" . urlencode('Format file harus CSV'));
        exit;
    }
    
    // Buka file CSV
    $handle = fopen($file['tmp_name'], 'r');
    if(!$handle) {
        header("Location: barang_import.php?msg=" . urlencode('Gagal membuka file CSV'));
        exit;
    }
    
    // Baca header
    $header = fgetcsv($handle);
    if(!$header) {
        fclose($handle);
        header("Location: barang_import.php?msg=" . urlencode('File CSV kosong'));
        exit;
    }
    
    // Normalisasi header
    $header = array_map(function($h) {
        return strtolower(trim($h));
    }, $header);
    
    // Mapping kolom
    $colMap = [
        'nama' => 'nama',
        'jenis' => 'jenis',
        'stok' => 'stok',
        'harga' => 'harga',
        'harga modal' => 'harga_modal',
        'harga_modal' => 'harga_modal',
        'stok minimum' => 'stok_min',
        'stok_min' => 'stok_min',
        'keterangan' => 'keterangan',
    ];
    
    $colIndex = [];
    foreach($header as $i => $h) {
        if(isset($colMap[$h])) {
            $colIndex[$colMap[$h]] = $i;
        }
    }
    
    // Validasi kolom wajib
    $required = ['nama', 'jenis', 'stok', 'harga', 'harga_modal', 'stok_min'];
    foreach($required as $req) {
        if(!isset($colIndex[$req])) {
            fclose($handle);
            header("Location: barang_import.php?msg=" . urlencode("Kolom wajib tidak ditemukan: $req"));
            exit;
        }
    }
    
    // Buat folder logs jika belum ada
    $logDir = '../logs';
    if(!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/import_barang.log';
    
    // Proses baris data
    $success = 0;
    $skipped = 0;
    $errors = [];
    $rowNum = 1;
    
    while(($row = fgetcsv($handle)) !== false) {
        $rowNum++;
        
        // Skip baris kosong
        if(count($row) < 2 || trim(implode('', $row)) === '') continue;
        
        $nama       = trim($row[$colIndex['nama']] ?? '');
        $jenis      = strtolower(trim($row[$colIndex['jenis']] ?? ''));
        $stok       = intval($row[$colIndex['stok']] ?? 0);
        $harga      = intval($row[$colIndex['harga']] ?? 0);
        $harga_modal = intval($row[$colIndex['harga_modal']] ?? 0);
        $stok_min   = isset($colIndex['stok_min']) && isset($row[$colIndex['stok_min']]) 
                        ? intval($row[$colIndex['stok_min']]) : 5;
        $keterangan = isset($colIndex['keterangan']) && isset($row[$colIndex['keterangan']]) 
                        ? trim($row[$colIndex['keterangan']]) : '';
        
        // Validasi
        if($nama === '') {
            $errors[] = "Baris $rowNum: Nama kosong";
            continue;
        }
        if(!in_array($jenis, ['barang', 'jasa'])) {
            $errors[] = "Baris $rowNum ($nama): Jenis harus 'barang' atau 'jasa' (dapat: $jenis)";
            continue;
        }
        if($jenis === 'jasa') {
            $stok = 0;
            $harga_modal = 0;
        }
        if($stok < 0) $stok = 0;
        if($harga < 0) $harga = 0;
        if($harga_modal < 0) $harga_modal = 0;
        if($stok_min < 0) $stok_min = 5;
        
        // Cek duplikat
        if($skipDuplicate) {
            $cek = mysqli_query($conn, "SELECT id_brg FROM barangjasa 
                WHERE nama='" . mysqli_real_escape_string($conn, $nama) . "' 
                AND jenis='" . mysqli_real_escape_string($conn, $jenis) . "' 
                LIMIT 1");
            if(mysqli_num_rows($cek) > 0) {
                $skipped++;
                continue;
            }
        }
        
        // Insert
        $namaEsc      = mysqli_real_escape_string($conn, $nama);
        $jenisEsc     = mysqli_real_escape_string($conn, $jenis);
        $ketEsc       = mysqli_real_escape_string($conn, $keterangan);
        
        $sql = "INSERT INTO barangjasa (nama, jenis, stok, harga, harga_modal, stok_min, keterangan, id_adm)
                VALUES ('$namaEsc', '$jenisEsc', '$stok', '$harga', '$harga_modal', '$stok_min', '$ketEsc', '1')";
        
        if(mysqli_query($conn, $sql)) {
            $success++;
            $logMsg = date('Y-m-d H:i:s') . " | SUCCESS | $nama | $jenis | Stok:$stok | Harga:$harga | Modal:$harga_modal | Min:$stok_min\n";
        } else {
            $err = mysqli_error($conn);
            $errors[] = "Baris $rowNum ($nama): $err";
            $logMsg = date('Y-m-d H:i:s') . " | ERROR | $nama | $err\n";
        }
        
        // Log ke file
        file_put_contents($logFile, $logMsg, FILE_APPEND);
    }
    
    fclose($handle);
    
    // Log summary
    $summary = date('Y-m-d H:i:s') . " | SUMMARY | Sukses:$success | Dilewati:$skipped | Error:" . count($errors) . "\n";
    file_put_contents($logFile, $summary, FILE_APPEND);
    
    // Redirect dengan hasil
    $msg = "Import selesai: $success data berhasil";
    if($skipped > 0) $msg .= ", $skipped dilewati (duplikat)";
    if(count($errors) > 0) $msg .= ", " . count($errors) . " error";
    
    $url = 'barang_import.php?msg=' . urlencode($msg);
    if(count($errors) > 0) {
        $url .= '&errors=' . urlencode(implode('|', array_slice($errors, 0, 10)));
    }
    header("Location: $url");
    exit;
} else {
    header("Location: barang_import.php");
    exit;
}
?>