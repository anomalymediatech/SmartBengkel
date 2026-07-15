<?php
/**
 * Property-Based Test: P3 — JSON dashboard_data memiliki struktur kontinu dan lengkap
 * Feature: dashboard-grafik, Property 3: JSON dashboard_data memiliki struktur kontinu dan lengkap
 *
 * Validates: Requirements 3.3, 3.4, 8.5
 *
 * Menguji logika pembangunan array labels+data dari dashboard_data.php secara terisolasi,
 * tanpa memerlukan koneksi database atau sesi web.
 *
 * Properti yang diverifikasi:
 *   - count($labels) == jumlah hari dalam bulan tersebut
 *   - count($data) == count($labels)
 *   - Setiap posisi yang tidak ada di lookup berisi 0.0 (float), bukan null atau string
 *   - Setiap elemen $data bertipe int atau float, bukan string
 */

// ── Fungsi yang diuji: logika gap-filling dari dashboard_data.php ──────────────

/**
 * Membangun array [labels, data] persis seperti yang dilakukan dashboard_data.php
 * untuk bulan dengan $days_in_month hari, dengan data transaksi sebagai lookup array.
 *
 * @param int   $days_in_month  Jumlah hari dalam bulan (28–31)
 * @param array $lookup         [hari_int => omset_float]  — subset hari yang ada data
 * @return array{labels: string[], data: float[]}
 */
function build_dashboard_json(int $days_in_month, array $lookup): array
{
    $labels = [];
    $data   = [];
    for ($d = 1; $d <= $days_in_month; $d++) {
        $labels[] = (string)$d;
        $data[]   = isset($lookup[$d]) ? $lookup[$d] : 0.0;
    }
    return ['labels' => $labels, 'data' => $data];
}

// ── Generator helpers ──────────────────────────────────────────────────────────

/** Pilih jumlah hari dalam bulan secara acak (28, 29, 30, atau 31) */
function random_days_in_month(): int
{
    return (int)[28, 29, 30, 31][array_rand([28, 29, 30, 31])];
}

/**
 * Hasilkan lookup array dengan subset acak hari (0 hingga $days_in_month hari)
 * masing-masing dengan omset float acak 0–9_999_999.
 */
function random_lookup(int $days_in_month): array
{
    $lookup = [];
    // pilih subset acak hari-hari yang punya transaksi
    $num_days_with_data = random_int(0, $days_in_month);
    $days_pool = range(1, $days_in_month);
    shuffle($days_pool);
    $selected_days = array_slice($days_pool, 0, $num_days_with_data);
    foreach ($selected_days as $day) {
        $lookup[$day] = (float)random_int(0, 9_999_999) + (random_int(0, 99) / 100.0);
    }
    return $lookup;
}

// ── Property assertions ────────────────────────────────────────────────────────

/**
 * Jalankan semua assertion P3 terhadap satu pasang ($days_in_month, $lookup).
 * Lempar Exception jika ada yang gagal.
 */
function assert_p3(int $days_in_month, array $lookup): void
{
    $result = build_dashboard_json($days_in_month, $lookup);
    $labels = $result['labels'];
    $data   = $result['data'];

    // P3-A: Panjang labels tepat sama dengan jumlah hari dalam bulan
    if (count($labels) !== $days_in_month) {
        throw new \RuntimeException(
            "P3-A gagal: count(labels)=" . count($labels) . " != days_in_month=$days_in_month"
        );
    }

    // P3-B: Panjang data sama dengan panjang labels
    if (count($data) !== count($labels)) {
        throw new \RuntimeException(
            "P3-B gagal: count(data)=" . count($data) . " != count(labels)=" . count($labels)
        );
    }

    for ($i = 0; $i < $days_in_month; $i++) {
        $day = $i + 1;

        // P3-C: label ke-i adalah string representasi hari
        if ($labels[$i] !== (string)$day) {
            throw new \RuntimeException(
                "P3-C gagal: labels[$i]=" . var_export($labels[$i], true) . " harus '" . (string)$day . "'"
            );
        }

        // P3-D: tipe data[i] harus int atau float, bukan string atau null
        if (!is_int($data[$i]) && !is_float($data[$i])) {
            throw new \RuntimeException(
                "P3-D gagal: data[$i] bertipe " . gettype($data[$i]) . " (harus int/float)"
            );
        }

        // P3-E: jika hari ini tidak ada di lookup, harus berisi 0.0
        if (!isset($lookup[$day])) {
            if ($data[$i] !== 0.0) {
                throw new \RuntimeException(
                    "P3-E gagal: data[$i] harus 0.0 untuk hari $day yang tidak ada di lookup, " .
                    "dapat: " . var_export($data[$i], true)
                );
            }
        } else {
            // P3-F: jika ada di lookup, nilainya harus konsisten
            if ($data[$i] !== $lookup[$day]) {
                throw new \RuntimeException(
                    "P3-F gagal: data[$i]=" . var_export($data[$i], true) .
                    " != lookup[$day]=" . var_export($lookup[$day], true)
                );
            }
        }
    }
}

// ── Main: jalankan 100 iterasi ─────────────────────────────────────────────────

$iterations = 100;
$passed     = 0;
$failed     = 0;
$counterexample = null;

for ($i = 0; $i < $iterations; $i++) {
    $days   = random_days_in_month();
    $lookup = random_lookup($days);
    try {
        assert_p3($days, $lookup);
        $passed++;
    } catch (\RuntimeException $e) {
        $failed++;
        if ($counterexample === null) {
            $counterexample = [
                'iteration'    => $i,
                'days_in_month'=> $days,
                'lookup'       => $lookup,
                'error'        => $e->getMessage(),
            ];
        }
    }
}

// ── Laporan hasil ──────────────────────────────────────────────────────────────

echo "=== Property Test P3: JSON dashboard_data struktur kontinu dan lengkap ===" . PHP_EOL;
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
    echo "  lookup keys  : " . implode(', ', array_keys($counterexample['lookup'])) . PHP_EOL;
    echo "  Error        : " . $counterexample['error'] . PHP_EOL;
    exit(1);
}
