<?php
/**
 * Secure Authentication System v2.0
 *
 * Fitur keamanan:
 * - Prepared statements untuk mencegah SQL Injection
 * - Password hashing dengan bcrypt (password_verify)
 * - CSRF Protection
 * - Secure session settings
 */

// Mulai session dengan settings aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database
include("dist/config/koneksi.php");

// Paksa HTTPS di production
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'on') {
    // Uncomment baris ini di production:
    // header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    // exit;
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function untuk sanitasi input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Verifikasi CSRF token
function verify_csrf($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
// LOGIN PROCESS
// ============================================================

if (isset($_POST['login'])) {

    // 1. Verifikasi CSRF Token
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        header("location: login.php?err=csrf");
        exit;
    }

    // 2. Validasi Input
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['akses'])) {
        header("location: login.php?err=empty");
        exit;
    }

    // 3. Sanitasi Input
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password']; // Jangan sanitasi password sebelum verifikasi
    $akses    = sanitize_input($_POST['akses']);

    // Validasi akses role
    if (!in_array($akses, ['Admin', 'Kasir'])) {
        header("location: login.php?err=invalid_role");
        exit;
    }

    // 4. Prepared Statement untuk mencegah SQL Injection
    if ($akses == "Admin") {
        // Login sebagai Admin
        $stmt = $conn->prepare("SELECT id_adm, user_adm, pass_adm FROM admin WHERE user_adm = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Verifikasi password (support both old plaintext and new hashed)
            $password_valid = false;

            // Cek apakah password sudah di-hash
            if (substr($row['pass_adm'], 0, 4) === '$2y$' || substr($row['pass_adm'], 0, 4) === '$2a$') {
                // Password ter-hash, gunakan password_verify
                $password_valid = password_verify($password, $row['pass_adm']);
            } else {
                // Password lama plaintext - fallback untuk migrasi
                $password_valid = ($password === $row['pass_adm']);
            }

            if ($password_valid) {
                // Regenerasi session ID untuk keamanan
                session_regenerate_id(true);

                $_SESSION['admin'] = strtolower($row['id_adm']);
                $_SESSION['login_time'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

                // Redirect dengan pesan sukses
                header("location: index.php?login=success");
                exit;
            }
        }

        $stmt->close();

    } elseif ($akses == "Kasir") {
        // Login sebagai Kasir
        $stmt = $conn->prepare("SELECT id_kasir, user_kasir, pass_kasir, nama_kasir, foto_kasir FROM kasir WHERE user_kasir = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Verifikasi password (support both old plaintext and new hashed)
            $password_valid = false;

            if (substr($row['pass_kasir'], 0, 4) === '$2y$' || substr($row['pass_kasir'], 0, 4) === '$2a$') {
                $password_valid = password_verify($password, $row['pass_kasir']);
            } else {
                $password_valid = ($password === $row['pass_kasir']);
            }

            if ($password_valid) {
                session_regenerate_id(true);

                $_SESSION['kasir'] = strtolower($row['id_kasir']);
                $_SESSION['kasirname'] = $row['nama_kasir'];
                $_SESSION['kasirfoto'] = $row['foto_kasir'];
                $_SESSION['login_time'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

                header("location: kasir/index.php?login=success");
                exit;
            }
        }

        $stmt->close();
    }

    // Jika sampai di sini, login gagal
    header("location: login.php?err=invalid");
    exit;
}

// Logout process
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Hapus semua session
    $_SESSION = array();

    // Hapus session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    header("location: login.php");
    exit;
}
?>