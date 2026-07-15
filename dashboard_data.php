<?php
include("sess_check.php");

// Ambil jumlah hari dalam bulan berjalan
$days_in_month = (int)date('t');

// Query omset per hari untuk bulan berjalan
$sql = "SELECT DAY(tgl_trx) AS hari, SUM(total) AS omset
        FROM trx
        WHERE MONTH(tgl_trx) = MONTH(CURDATE())
          AND YEAR(tgl_trx)  = YEAR(CURDATE())
          AND status_bayar != 'Batal'
        GROUP BY DAY(tgl_trx)";

$res = mysqli_query($conn, $sql);

// Bangun lookup array: [hari => omset]
$lookup = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $lookup[(int)$row['hari']] = (float)$row['omset'];
    }
}

// Isi array label dan data untuk setiap hari dalam bulan
$labels = [];
$data   = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $labels[] = (string)$d;
    $data[]   = isset($lookup[$d]) ? $lookup[$d] : 0.0;
}

// Output JSON
header('Content-Type: application/json');
echo json_encode(["labels" => $labels, "data" => $data]);
