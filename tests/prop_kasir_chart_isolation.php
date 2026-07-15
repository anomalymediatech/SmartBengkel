<?php
/**
 * Property-Based Test: P4 — Isolasi data kasir pada grafik batang
 * Feature: dashboard-grafik, Property 4: Isolasi data kasir pada grafik batang
 *
 * Validates: Requirements 4.2
 *
 * Menguji logika isolasi data dari kasir/dashboard_data.php secara terisolasi,
 * tanpa memerlukan koneksi database atau sesi web.
 *
 * Properti yang diverifikasi:
 *   - Setiap nilai dalam array data hanya menghitung transaksi yang id_kasirnya cocok
 *   - Transaksi dari kasir lain tidak ikut terhitung
 *   - Hari tanpa transaksi untuk kasir tersebut berisi 0 (int)
 */

// ── Fungsi yang diuji: logika isolasi + gap-filling dari kasir/dashboard_data.php ──

/**
 * Simulasi logika kasir/dashboard_data.php:
 * Dari daftar transaksi $all_trx, filter hanya yang id_kasir == $target_kasir,
 * agregasi COUNT per hari, lalu isi gap dengan 0.
 *
 * @param int   $days_in_month  Jumlah hari dalam bulan
 * @param int   $target_kasir   id_kasir yang sedang login
 * @param array $all_trx        Array of ['hari'=>int, 'id_kasir'=>int]  — semua transaksi bulan ini
 * @return array{labels: string[], data: int[]}
 */
function build_kasir_chart_json(int $days_in_month, int $target_kasir, array $all_trx): array
{
    // Bangun lookup: hanya transaksi milik $target_kasir
    $lookup = [];
    foreach ($all_trx as $trx) {
        if ((int)$trx['id_kasir'] === $target_kasir) {
            $hari = (int)$trx['hari'];
            $lookup[$hari] = ($lookup[$hari] ?? 0) + 1;
        }
    }

    // Isi array labels dan data untuk setiap hari
    $labels = [];
    $data   = [];
    for ($d = 1; $d <= $days_in_month; $d++) {
        $labels[] = (string)$d;
        $data[]   = isset($lookup[$d]) ? $lookup[$d] : 0;
    }

    return ['labels' => $labels, 'data' => $data];
}

// ── Generator helpers ──────────────────────────────────────────────────────────

/** Pilih jumlah hari dalam bulan secara acak */
function random_days_kasir(): int
{
    return (int)[28, 29, 30, 31][array_rand([28, 29, 30, 31])];
}

/**
 * Hasilkan array transaksi acak dari beberapa kasir berbeda.
 * Setiap transaksi memiliki: ['hari' => 1–N, 'id_kasir' => 1–max_kasir]
 */
function random_trx_pool(int $days_in_month, int $max_kasir_id, int $trx_count): array
{
    $pool = [];
    for ($i = 0; $i < $trx_count; $i++) {
        $pool[] = [
            'hari'     => random_int(1, $days_in_month),
            'id_kasir' => random_int(1, $max_kasir_id),
        ];
    }
    return $pool;
}

// ── Property assertions ────────────────────────────────────────────────────────

/**
 * P4: Verifikasi isolasi data kasir.
 * Semua nilai data[i] harus hanya berasal dari transaksi target_kasir.
 */
function assert_p4(int $days_in_month, int $target_kasir, array $all_trx): void
{
    $result = build_kasir_chart_json($days_in_month, $target_kasir, $all_trx);
    $labels = $result['labels'];
    $data   = $result['data'];

    // P4-A: Panjang labels dan data harus tepat
    if (count($labels) !== $days_in_month) {
        throw new \RuntimeException(
            "P4-A gagal: count(labels)=" . count($labels) . " != days_in_month=$days_in_month"
        );
    }
    if (count($data) !== count($labels)) {
        throw new \RuntimeException(
            "P4-A gagal: count(data) != count(labels)"
        );
    }

    // Hitung manual transaksi per hari hanya untuk target_kasir
    $expected = [];
    foreach ($all_trx as $trx) {
        if ((int)$trx['id_kasir'] === $target_kasir) {
            $h = (int)$trx['hari'];
            $expected[$h] = ($expected[$h] ?? 0) + 1;
        }
    }

    for ($i = 0; $i < $days_in_month; $i++) {
        $day      = $i + 1;
        $exp_val  = $expected[$day] ?? 0;
        $got_val  = $data[$i];

        // P4-B: Tipe data[i] harus int (bukan string)
        if (!is_int($got_val)) {
            throw new \RuntimeException(
                "P4-B gagal: data[$i] bertipe " . gettype($got_val) . " (harus int)"
            );
        }

        // P4-C: Nilai harus persis = jumlah transaksi target_kasir pada hari itu
        if ($got_val !== $exp_val) {
            throw new \RuntimeException(
                "P4-C gagal: hari=$day, kasir=$target_kasir: data[$i]=$got_val != expected=$exp_val"
            );
        }
    }

    // P4-D: Verifikasi bahwa transaksi kasir lain TIDAK ikut terhitung
    // Total data harus tepat sama dengan jumlah transaksi target_kasir saja
    $total_in_data = array_sum($data);
    $total_target  = count(array_filter($all_trx, fn($t) => (int)$t['id_kasir'] === $target_kasir));
    if ($total_in_data !== $total_target) {
        throw new \RuntimeException(
            "P4-D gagal: total data=$total_in_data != transaksi kasir $target_kasir=$total_target " .
            "(transaksi kasir lain bocor ke dalam data)"
        );
    }
}

// ── Main: jalankan 100 iterasi ─────────────────────────────────────────────────

$iterations = 100;
$passed     = 0;
$failed     = 0;
$counterexample = null;

for ($i = 0; $i < $iterations; $i++) {
    $days         = random_days_kasir();
    $max_kasir    = random_int(2, 5);              // 2–5 kasir berbeda
    $target_kasir = random_int(1, $max_kasir);     // pilih satu sebagai target
    $trx_count    = random_int(0, 50);             // 0–50 transaksi dalam pool
    $all_trx      = random_trx_pool($days, $max_kasir, $trx_count);

    try {
        assert_p4($days, $target_kasir, $all_trx);
        $passed++;
    } catch (\RuntimeException $e) {
        $failed++;
        if ($counterexample === null) {
            $counterexample = [
                'iteration'    => $i,
                'days_in_month'=> $days,
                'target_kasir' => $target_kasir,
                'max_kasir'    => $max_kasir,
                'trx_count'    => count($all_trx),
                'error'        => $e->getMessage(),
            ];
        }
    }
}

// ── Laporan hasil ──────────────────────────────────────────────────────────────

echo "=== Property Test P4: Isolasi data kasir pada grafik batang ===" . PHP_EOL;
echo "Iterasi  : $iterations" . PHP_EOL;
echo "Lulus    : $passed" . PHP_EOL;
echo "Gagal    : $failed" . PHP_EOL;

if ($failed === 0) {
    echo "STATUS   : PASSED ✓" . PHP_EOL;
    exit(0);
} else {
    echo "STATUS   : FAILED ✗" . PHP_EOL;
    echo PHP_EOL . "Counter-example pertama:" . PHP_EOL;
    echo "  Iterasi     : " . $counterexample['iteration'] . PHP_EOL;
    echo "  days_in_month: " . $counterexample['days_in_month'] . PHP_EOL;
    echo "  target_kasir : " . $counterexample['target_kasir'] . PHP_EOL;
    echo "  max_kasir    : " . $counterexample['max_kasir'] . PHP_EOL;
    echo "  trx_count    : " . $counterexample['trx_count'] . PHP_EOL;
    echo "  Error        : " . $counterexample['error'] . PHP_EOL;
    exit(1);
}
