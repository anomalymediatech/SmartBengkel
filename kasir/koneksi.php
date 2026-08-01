<?php
/**
 * Database Configuration Loader (Kasir)
 * Loads from .env file if exists, falls back to defaults
 */

function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (preg_match('/^["\'](.*)["\']$/', $value, $m)) {
            $value = $m[1];
        }
        if (function_exists('putenv')) @putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function envOr($key, $default) {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    return $default;
}

loadEnv();

$dbhost = envOr('DB_HOST', 'localhost');
$dbuser = envOr('DB_USER', 'root');
$dbpass = envOr('DB_PASS', '');
$dbname = envOr('DB_NAME', 'db_bengkel');

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if (!$conn) {
    die("Tidak dapat terhubung ke database: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');