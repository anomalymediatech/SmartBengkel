# Deploy SmartBengkel ke InfinityFree (Gratis Selamanya)

InfinityFree = hosting PHP + MySQL **gratis selamanya**, tanpa kartu kredit, tanpa masa trial.
Cocok untuk presentasi / demo project ini.

- PHP 8.3, MySQL via phpMyAdmin, SSL gratis, 5 GB storage.
- Tanpa SSH, tanpa cron, CPU di-throttle saat ramai (tidak masalah untuk demo).

---

## Persiapan (1x di mesin kamu)

1. Pastikan project berjalan benar di lokal (sudah diverifikasi: admin + kasir login OK).
2. Siapkan kredensial DB **InfinityFree** (lihat Langkah B), lalu isi file `.env`:

   ```
   DB_HOST=<host database dari InfinityFree, mis. sql123.infinityfree.com>
   DB_PORT=3306
   DB_USER=<username DB dari InfinityFree, mis. if0_12345678>
   DB_PASS=<password DB>
   DB_NAME=<nama DB dari InfinityFree, mis. if0_12345678_db_bengkel>
   ```

   > Jangan pernah commit `.env` ke git (sudah ada di `.gitignore`).

---

## Langkah A — Buat akun & website

1. Buka https://www.infinityfree.com → **Register** (pakai email biasa, tanpa kartu kredit).
2. Masuk ke **Client Area** → klik **Create Account** → pilih tipe **Website** → **Create a free hosting account**.
3. Isi nama subdomain, mis. `smartbengkel.infinityfreeapp.com` (atau pakai domain sendiri bila punya).
4. Selesai → kamu dapat akses ke **Control Panel** (vPanel).

## Langkah B — Buat database MySQL

1. Di Control Panel, buka menu **MySQL Databases**.
2. Klik **Create a database** → catat **semua** kredensial yang muncul:
   - Host database (mis. `sql123.infinityfree.com`)
   - Username (mis. `if0_12345678_user`)
   - Password
   - Nama database (mis. `if0_12345678_db_bengkel`)
3. Isi kredensial ini ke file `.env` (lihat bagian Persiapan).

## Langkah C — Import skema database

1. Di Control Panel → **MySQL Databases** → klik **phpMyAdmin** pada database yang dibuat.
2. Pilih database di panel kiri.
3. Tab **Import** → Choose File → pilih `database/db_bengkel.sql` → **Go**.
4. Ulangi import untuk `upgrade_v2.sql` (juga di folder `database/` atau root project).

> Urutan penting: import `db_bengkel.sql` DULU, baru `upgrade_v2.sql`.

## Langkah D — Upload file aplikasi

Ada dua cara:

### Cara 1: File Manager (paling mudah, untuk pemula)
1. Di Control Panel → **File Manager**.
2. Masuk ke folder `htdocs/`.
3. Upload file `smartbengkel-upload.zip` (buat sendiri: zip isi project di luar folder `temp_repo`, `.git`, dan `tests`).
4. Klik kanan zip → **Extract** → hasilnya ter-extract ke `htdocs/`.

### Cara 2: FTP (direkomendasikan, lebih cepat untuk banyak file)
1. Pakai aplikasi FTP: **FileZilla** (gratis).
2. Info koneksi ada di Control Panel → **FTP Details** (host, user, pass, port 21).
3. Upload **isi** folder project ke `htdocs/` (bukan folder project-nya, tapi isinya langsung di `htdocs/`).
4. Jangan upload: `temp_repo/`, `tests/`, `.git/`.

## Langkah E — Isi `.env` & set PHP version

1. Via File Manager: edit file `.env` yang sudah terupload → isi kredensial DB InfinityFree.
2. Di Control Panel → **PHP Configuration**: pastikan versi PHP = **8.x** (InfinityFree menyediakan 8.3).

## Langkah F — Seed data contoh

1. Buka di browser: `https://smartbengkel.infinityfreeapp.com/setup_sample_data.php`
2. Harus muncul log seeding (`+21 barang/jasa`, `+7 transaksi contoh`, dll).
3. SETELAH sukses, **hapus** file `setup_sample_data.php` lewat File Manager (biar tidak bisa dijalankan orang lain).
4. Login:
   - Admin: `admin` / `admin`
   - Kasir: `kasir` / `password`

## Langkah G — Verifikasi

1. Buka `https://<subdomain>/index.php` → login admin → dashboard tampil.
2. Buka `https://<subdomain>/kasir/index.php` → login kasir → beranda kasir tampil.
3. Cek menu: Transaksi Baru, Laporan Laba, Piutang/Hutang (ada data contoh).

---

## Catatan penting

- **Aktivasi berkala**: InfinityFree menghapus akun yang tidak login ke Control Panel selama ~60 hari. Untuk presentasi, login Control Panel 1x sebelum demo.
- **Batasan**: 5 GB storage, tanpa email, tanpa SSH, tanpa cron. `foto/` upload file kecil tetap jalan.
- **`.htaccess`**: sudah disertakan untuk memblokir akses langsung ke `.env` & file SQL.

## Troubleshooting

- **"Tidak dapat terhubung ke database"** → cek `.env` (host DB ≠ `localhost`; pakai host `sql....infinityfree.com`).
- **Halaman putih 500** → cek versi PHP (harus 8.x) & pastikan `.htaccess` tidak menghalangi.
- **Import SQL gagal** → pastikan urutan import benar & gunakan phpMyAdmin dari Control Panel.
- **Login gagal** → pastikan `setup_sample_data.php` sudah dijalankan & session cookie aktif.
