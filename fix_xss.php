<?php
/**
 * XSS Protection Fixer
 * Automatically adds htmlspecialchars() to unescaped database outputs
 */

$files_to_fix = [
    'kasir/jasa.php',
    'kasir/supplier.php',
    'kasir/konsumen.php',
    'trx_v2.php',
    'laporan_trx.php',
    'laporan_trx_cetak.php',
    'struk_thermal.php',
    'barang_stok.php'
];

$patterns = [
    // Pattern 1: echo $data['field'] or $row['field'] without htmlspecialchars
    '/echo\s+\'([^\']*)<td[^>]*>\'\s*\.\s*\$(?:data|row|res|li|k|result)\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]]\s*\./' =>
        "echo '$1<td>' . htmlspecialchars(\$data['$2']) .",

    // Pattern 2: Direct variable output in string
    '/echo\s+\$(?:data|row|res|li|k|result)\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]]\s*;/' =>
        "echo htmlspecialchars(\$data['$1']);",

    // Pattern 3: In confirmation dialog
    '/onclick="return confirm\([\'"]([^\'"]*)\$(?:data|row)\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]]\s*([^"]*)[\'"]/' =>
        'onclick="return confirm(\'$1\' . htmlspecialchars(\$data[\'$2\']) . \'$3\''
];

foreach ($files_to_fix as $file) {
    $full_path = __DIR__ . '/' . $file;

    if (!file_exists($full_path)) {
        echo "[SKIP] File not found: $file\n";
        continue;
    }

    $content = file_get_contents($full_path);
    $original = $content;

    // Fix Pattern 1: echo '<td>' . $data['field'] .
    $content = preg_replace_callback(
        '/echo\s+\'([^\']*)<td[^>]*>\'\s*\.\s*\$(?:data|row|result)\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]]\s*\./',
        function($m) {
            return "echo '$m[1]<td>' . htmlspecialchars(\$data['$m[2]']) .";
        },
        $content
    );

    // Fix Pattern 2: Direct unescaped variable in echo
    $content = preg_replace_callback(
        '/echo\s+\'([^\']*)\'\s*\.\s*\$(?:data|row|result|li|k)\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]]\s*(.?)/',
        function($m) {
            $end = $m[3] === ';' ? ';' : ' .';
            return "echo '$m[1]' . htmlspecialchars(\$data['$m[2]'])$end";
        },
        $content
    );

    // Fix Pattern 3: onclick confirm with unescaped variable
    $content = preg_replace_callback(
        '/onclick="return confirm\(\'([^\']*)\$(?:data|row)\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]]\s*([^"]*)\'\s*\);/',
        function($m) {
            return "onclick=\"return confirm('$m[1]' . htmlspecialchars(\$data['$m[2]']) . '$m[3]');";
        },
        $content
    );

    if ($content !== $original) {
        file_put_contents($full_path, $content);
        echo "[FIXED] $file\n";
    } else {
        echo "[OK] $file (no changes needed)\n";
    }
}

echo "\nDone!\n";
?>