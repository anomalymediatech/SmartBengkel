<?php
include("sess_check.php");

// Ambil ID kasir dari sesi, cast ke int untuk keamanan
$id_kasir = (int)$_SESSION['kasir'];

// Jumlah hari dalam bulan berjalan
$days_in_month = (int)date('t');

// Query jumlah transaksi per hari untuk kasir ini di bulan berjalan
// Menggunakan intval() pada $id_kasir sudah dilakukan di atas (cast int)
$sql = "SELECT DAY(tgl_trx) AS hari, COUNT(id_trx) AS jml_trx
        FROM trx
        WHERE MONTH(tgl_trx) = MONTH(CURDATE())
          AND YEAR(tgl_trx)  = YEAR(CURDATE())
          AND id_kasir = $id_kasir
          AND status_bayar != 'Batal'
        GROUP BY DAY(tgl_trx)";

$res = mysqli_query($conn, $sql);

// Bangun lookup array: [hari => jml_trx]
$lookup = [];
while ($row = mysqli_fetch_assoc($res)) {
    $lookup[(int)$row['hari']] = (int)$row['jml_trx'];
}

// Isi array label dan data untuk setiap hari dalam bulan
$labels = [];
$data   = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $labels[] = (string)$d;
    $data[]   = isset($lookup[$d]) ? $lookup[$d] : 0;
}

// Kirim respons JSON
header('Content-Type: application/json');
echo json_encode(["labels" => $labels, "data" => $data]);
