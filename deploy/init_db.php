<?php
/**
 * SmartBengkel - Inisialisasi Database (dijalankan saat container start)
 *
 * Langkah:
 *   1. Konek ke MySQL/MariaDB tanpa memilih database
 *   2. Buat database bila belum ada
 *   3. Import skema dasar (database/db_bengkel.sql)
 *   4. Import upgrade v2 (upgrade_v2.sql)
 *   5. Isi sample data (logika sama dengan setup_sample_data.php)
 *
 * Idempotent: aman dijalankan berkali-kali.
 */

$dbhost = getenv('DB_HOST') ?: 'db';
$dbport = getenv('DB_PORT') ?: 3306;
$dbuser = getenv('DB_USER') ?: 'root';
$dbpass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'db_bengkel';

echo "  koneksi: {$dbuser}@{$dbhost}:{$dbport}\n";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, '', (int)$dbport);
if (!$conn) {
    fwrite(STDERR, "GAGAL konek MySQL: " . mysqli_connect_error() . "\n");
    exit(1);
}
mysqli_set_charset($conn, 'utf8mb4');

// 1. Buat database
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4");
mysqli_select_db($conn, $dbname);
echo "  database '{$dbname}' siap\n";

// 2. Import skema dasar
$base = __DIR__ . '/../database/db_bengkel.sql';
if (file_exists($base)) {
    $sql = file_get_contents($base);
    if (!runMulti($conn, $sql)) { fwrite(STDERR, "GAGAL import skema dasar\n"); exit(1); }
    echo "  skema dasar di-import\n";
} else {
    fwrite(STDERR, "WARNING: database/db_bengkel.sql tidak ditemukan\n");
}

// 3. Import upgrade v2
$up = __DIR__ . '/../upgrade_v2.sql';
if (file_exists($up)) {
    $sql = file_get_contents($up);
    if (!runMulti($conn, $sql)) { fwrite(STDERR, "GAGAL import upgrade v2\n"); exit(1); }
    echo "  upgrade v2 di-import\n";
}

// 4. Sample data — pakai setup_sample_data.php (sudah teruji idempotent,
//    menangani backfill trx_detail lama & anti-duplikasi transaksi harian)
mysqli_close($conn);
require __DIR__ . '/../setup_sample_data.php';
echo "Inisialisasi selesai.\n";

function runMulti($conn, $sql) {
    // Hapus komentar baris untuk mengurangi risiko kesalahan parser
    $lines = preg_split('/\r?\n/', $sql);
    $clean = array();
    foreach ($lines as $line) {
        $l = trim($line);
        if ($l === '' || strpos($l, '--') === 0 || strpos($l, '/*') === 0 || strpos($l, '#') === 0) {
            continue;
        }
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);
    $hadError = false;
    if (mysqli_multi_query($conn, $sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
            $err = mysqli_error($conn);
            if ($err !== '') {
                // Duplicate key (1062) saat re-run: data sudah ada, anggap sukses (idempotent).
                if (mysqli_errno($conn) != 1062) {
                    fwrite(STDERR, "  SQL error: $err\n");
                    $hadError = true;
                }
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        return !$hadError;
    }
    $err = mysqli_error($conn);
    // Abaikan duplicate key saat re-run.
    if (mysqli_errno($conn) == 1062) {
        return true;
    }
    fwrite(STDERR, "  SQL error: $err\n");
    return false;
}
