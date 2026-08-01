# ============================================================
# SmartBengkel - Auto Setup Script untuk Windows PowerShell
# Jalankan: .\setup.ps1  (di folder SmartBengkel)
# ============================================================

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  SmartBengkel v2.0 - Auto Setup & Sample Data Generator" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Cek folder
$projectPath = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path $projectPath)) {
    Write-Host "ERROR: Folder $projectPath tidak ditemukan!" -ForegroundColor Red
    exit 1
}
Set-Location $projectPath
Write-Host "[OK] Folder project: $projectPath" -ForegroundColor Green

# 2. Setup .env
Write-Host "`n[1/6] Setup .env..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "  .env dibuat dari .env.example" -ForegroundColor Green
    } else {
        Write-Host "  .env.example tidak ditemukan, buat manual..." -ForegroundColor Yellow
        $envContent = @"
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=db_bengkel
"@
        $envContent | Out-File -Encoding utf8 ".env"
        Write-Host "  .env default dibuat" -ForegroundColor Green
    }
} else {
    Write-Host "  .env sudah ada" -ForegroundColor Green
}

# 3. Cek MySQL
Write-Host "`n[2/6] Cek MySQL..." -ForegroundColor Yellow
$mysqlPath = "mysql"
if (-not (Get-Command $mysqlPath -ErrorAction SilentlyContinue)) {
    $possiblePaths = @(
        "C:\Program Files\MariaDB 12.3\bin\mysql.exe",
        "C:\Program Files\MariaDB 11*\bin\mysql.exe",
        "C:\xampp\mysql\bin\mysql.exe",
        "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe",
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
    )
    foreach ($p in $possiblePaths) {
        $resolved = Get-ChildItem $p -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($resolved) {
            $mysqlPath = $resolved.FullName
            break
        }
    }
}
Write-Host "  MySQL: $mysqlPath" -ForegroundColor Green

# 4. Input password root MySQL (optional)
Write-Host "`n[3/6] Koneksi Database..." -ForegroundColor Yellow
$mysqlUser = "root"
$mysqlPass = ""
$mysqlHost = "localhost"
$dbName = "db_bengkel"

function Invoke-Mysql([string]$argsStr, [bool]$redirect = $false) {
    $redir = if ($redirect) { "< `"$redirFile`"" } else { "" }
    $cmd = "`"$mysqlPath`" -u$mysqlUser -h$mysqlHost"
    if ($mysqlPass) { $cmd += " -p$mysqlPass" }
    $cmd += " $argsStr"
    cmd /c $cmd
    return $LASTEXITCODE -eq 0
}

# Test koneksi
$ok = Invoke-Mysql "-e `"SELECT 1;`""
if ($ok) {
    Write-Host "  Koneksi MySQL OK" -ForegroundColor Green
} else {
    Write-Host "  Gagal konek MySQL. Coba masukkan password root:" -ForegroundColor Yellow
    $mysqlPass = Read-Host "  Password root MySQL (kosong jika default XAMPP)"
    $ok = Invoke-Mysql "-e `"SELECT 1;`""
    if ($ok) {
        Write-Host "  Koneksi MySQL OK" -ForegroundColor Green
    } else {
        Write-Host "  ERROR: Tidak bisa konek ke MySQL. Pastikan MySQL jalan & password benar." -ForegroundColor Red
        exit 1
    }
}

# 5. Buat database & import schema
Write-Host "`n[4/6] Setup Database & Schema..." -ForegroundColor Yellow
$null = Invoke-Mysql "-e `"CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4;`""
Write-Host "  Database '$dbName' siap" -ForegroundColor Green

# Import schema utama
$sqlFile1 = "temp_repo\database\db_bengkel.sql"
if (Test-Path $sqlFile1) {
    $redirFile = $sqlFile1
    $ok = Invoke-Mysql "$dbName" -redirect $true
    if ($ok) {
        Write-Host "  Schema utama di-import" -ForegroundColor Green
    } else {
        Write-Host "  ERROR: Gagal import $sqlFile1" -ForegroundColor Red
    }
} else {
    Write-Host "  WARNING: $sqlFile1 tidak ditemukan" -ForegroundColor Yellow
}

# Import upgrade v2
$sqlFile2 = "upgrade_v2.sql"
if (Test-Path $sqlFile2) {
    $redirFile = $sqlFile2
    $ok = Invoke-Mysql "$dbName" -redirect $true
    if ($ok) {
        Write-Host "  Upgrade v2 di-import" -ForegroundColor Green
    } else {
        Write-Host "  ERROR: Gagal import $sqlFile2" -ForegroundColor Red
    }
} else {
    Write-Host "  WARNING: $sqlFile2 tidak ditemukan" -ForegroundColor Yellow
}

# 6. Generate Sample Data
Write-Host "`n[5/6] Generate Sample Data..." -ForegroundColor Yellow
if (Test-Path "setup_sample_data.php") {
    php setup_sample_data.php
} else {
    Write-Host "  WARNING: setup_sample_data.php tidak ditemukan" -ForegroundColor Yellow
}

# 7. Test Property Tests
Write-Host "`n[6/6] Jalankan Property Tests..." -ForegroundColor Yellow
if (Test-Path "tests\prop_dashboard_data_json.php") { php tests\prop_dashboard_data_json.php }
if (Test-Path "tests\prop_kasir_chart_isolation.php") { php tests\prop_kasir_chart_isolation.php }

Write-Host "`n============================================================" -ForegroundColor Cyan
Write-Host "  SETUP SELESAI!" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Akses aplikasi:" -ForegroundColor Green
Write-Host "  Admin: http://localhost/SmartBengkel/   (admin / admin)" -ForegroundColor Cyan
Write-Host "  Kasir: http://localhost/SmartBengkel/kasir/ (kasir / password)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Atau jalankan PHP server:" -ForegroundColor Yellow
Write-Host "  php -S localhost:8080" -ForegroundColor Cyan
Write-Host "  Akses: http://localhost:8080/" -ForegroundColor Cyan
Write-Host ""
Read-Host "Tekan Enter untuk keluar..."
