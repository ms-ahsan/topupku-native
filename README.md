# TopUpKu - Toko Top-Up Digital (PHP Native)

TopUpKu adalah aplikasi **toko top-up produk digital** berbasis **PHP native tanpa framework** dengan **PDO + PostgreSQL**: voucher game, pulsa semua operator, dan token listrik PLN prabayar. Pembayaran dilakukan dengan **mengunggah bukti transfer** yang kemudian **diverifikasi admin** - pesanan baru diproses setelah pembayaran disetujui, dan kode voucher dialokasikan **otomatis dari stok** saat pesanan diselesaikan admin. UI responsif berbahasa Indonesia dengan design system "kios biru powder" yang sama dengan proyek saudaranya `topupku-fullstack`.

> Integrasi payment gateway dan supplier eksternal belum disertakan. Alur pembayaran sepenuhnya manual: bukti transfer pelanggan + verifikasi admin.

## Daftar Isi

1. [Teknologi & Prasyarat](#teknologi--prasyarat)
2. [Instalasi Lokal](#instalasi-lokal)
3. [Konfigurasi](#konfigurasi)
4. [Skema Database](#skema-database)
5. [Arsitektur & Alur Request](#arsitektur--alur-request)
6. [Struktur Direktori](#struktur-direktori)
7. [Referensi Fungsi Lengkap](#referensi-fungsi-lengkap)
8. [Alur Bisnis Transaksi](#alur-bisnis-transaksi)
9. [Siklus Status Transaksi](#siklus-status-transaksi)
10. [Peran & Hak Akses](#peran--hak-akses)
11. [Keamanan](#keamanan)
12. [Frontend & Desain](#frontend--desain)
13. [Akun Admin](#akun-admin)
14. [Troubleshooting](#troubleshooting)

## Teknologi & Prasyarat

| Komponen | Versi minimum | Keterangan |
| --- | --- | --- |
| PHP | 8.1 | Memakai return type `never`, ekspresi `match`, dan `declare(strict_types=1)` |
| Ekstensi PHP | - | `pdo_pgsql` (koneksi PostgreSQL) dan `fileinfo` (deteksi MIME unggahan via `finfo`) |
| PostgreSQL | 12 | Memakai identity column, `COUNT(*) FILTER (WHERE ...)`, dan `ILIKE` |

Tidak ada Composer, Node.js, atau build step - seluruh aset statis sudah tersedia di `assets/`.

## Instalasi Lokal

1. Pastikan prasyarat di atas tersedia.
2. Impor `database.sql` (membuat tabel, trigger, dan data awal):

   ```bash
   psql -f database.sql
   ```

   atau manual:

   ```bash
   createdb topupku
   psql -d topupku -f database.sql
   ```

3. Sesuaikan kredensial database (host, port, nama database, user, sandi) pada `config.php`.
4. Buat akun admin dari folder proyek (CLI):

   ```bash
   php create-admin.php admin@topupku.id 'GantiPasswordIni'
   ```

5. Jalankan server pengembangan dari folder proyek, lalu buka `http://localhost:8000`:

   ```bash
   php -S localhost:8000
   ```

6. Pastikan PHP/Apache dapat **menulis** ke folder `uploads/` (bukti pembayaran dan gambar produk).

> Bisa juga diakses via Apache/XAMPP (`http://localhost/php/topupku-native/`); `.htaccess` root sudah memblokir akses web langsung ke `config.php`, `database.sql`, `README.md`, dan `create-admin.php`.

## Konfigurasi

Semua konstanta aplikasi didefinisikan di `config.php`:

| Konstanta | Nilai bawaan | Keterangan |
| --- | --- | --- |
| `DB_HOST` | `'127.0.0.1'` | Host server PostgreSQL |
| `DB_PORT` | `'5432'` | Port PostgreSQL |
| `DB_NAME` | `'topupku'` | Nama database |
| `DB_USER` | `'postgres'` | User database |
| `DB_PASS` | `''` | Sandi database - **wajib diganti** di lingkungan sungguhan |
| `APP_NAME` | `'TopUpKu'` | Nama aplikasi |
| `UPLOAD_DIR` | `__DIR__ . '/uploads'` | Folder tujuan unggahan (bukti bayar + gambar produk) |

`config.php` juga mengatur:

- `session_set_cookie_params` dengan `httponly => true` dan `samesite => 'Lax'`, lalu `session_start()`.
- `date_default_timezone_set('Asia/Jakarta')`.
- Koneksi PDO ke PostgreSQL dengan DSN `pgsql:host=...;port=...;dbname=...;options='--client_encoding=UTF8'` serta opsi `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (error melempar exception), `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (hasil query berupa array asosiatif), dan `PDO::ATTR_EMULATE_PREPARES => false` (prepared statement asli di server).
- Jika koneksi gagal: HTTP 500 dengan petunjuk membuat database `topupku`, mengimpor `database.sql` via psql, dan menyesuaikan kredensial di `config.php`.

## Skema Database

Skema lengkap ada di `database.sql` (PostgreSQL 12+). Enam tabel utama:

### users

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id_user` | INT IDENTITY PK | |
| `nama` | VARCHAR(100) NOT NULL | |
| `email` | VARCHAR(100) NOT NULL UNIQUE | |
| `no_hp` | VARCHAR(15) NOT NULL UNIQUE | |
| `password` | VARCHAR(255) NOT NULL | Hash `password_hash()` |
| `role` | VARCHAR(20) | `admin` / `pelanggan`, default `pelanggan` (CHECK) |
| `status` | VARCHAR(20) | `aktif` / `nonaktif`, default `aktif` (CHECK) |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

### categories

`id_kategori` (PK identity), `nama_kategori` VARCHAR(50), `deskripsi` VARCHAR(255) NULL.

### products

`id_produk` (PK identity), `id_kategori` FK -> `categories`, `nama_produk` VARCHAR(100), `provider` VARCHAR(50), `nominal` INT, `harga_jual` NUMERIC(12,2), `deskripsi` TEXT NULL, `gambar` VARCHAR(255) NULL (nama file di `uploads/`), `status` VARCHAR(20) CHECK `aktif`/`nonaktif` default `aktif`.

### voucher_stocks

`id_stok` (PK identity), `id_produk` FK -> `products`, `kode_voucher` VARCHAR(100) UNIQUE, `status` VARCHAR(20) CHECK `tersedia`/`terpakai` default `tersedia`, `created_at`. Satu baris = satu kode voucher fisik; "stok" produk voucher = jumlah baris berstatus `tersedia`.

### transactions

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id_transaksi` | INT IDENTITY PK | |
| `kode_transaksi` | VARCHAR(20) NOT NULL UNIQUE | Format `TK` + `yymmdd` + 6 hex acak, dibuat `create_order_code()` |
| `id_user` | INT FK -> `users` | |
| `id_produk` | INT FK -> `products` | |
| `id_stok` | INT NULL FK -> `voucher_stocks` | `ON DELETE SET NULL`; terisi saat voucher dialokasikan |
| `nomor_tujuan` | VARCHAR(50) NOT NULL | Nomor HP / ID pelanggan PLN / ID game |
| `total_bayar` | NUMERIC(12,2) NOT NULL | |
| `status` | VARCHAR(30) CHECK | 6 status, lihat [Siklus Status Transaksi](#siklus-status-transaksi) |
| `hasil` | VARCHAR(255) NULL | Catatan hasil / kode voucher yang dikirim |
| `created_at`, `updated_at` | TIMESTAMP | `updated_at` diperbarui trigger |

Indeks: `idx_transactions_status`, `idx_transactions_created_at`.

### payments

`id_pembayaran` (PK identity), `id_transaksi` FK **UNIQUE** (hubungan 1:1 dengan transaksi), `metode` VARCHAR(20) CHECK `transfer_bank`/`e_wallet`/`qris`, `jumlah` NUMERIC(12,2), `bukti_bayar` VARCHAR(255) (nama file), `status` VARCHAR(20) CHECK `menunggu`/`terverifikasi`/`ditolak` default `menunggu`, `catatan` VARCHAR(255) NULL (alasan penolakan), `diverifikasi_oleh` INT NULL FK -> `users`, `waktu_bayar` TIMESTAMP, `waktu_verifikasi` TIMESTAMP NULL.

### Trigger & data awal

- Trigger `trg_transactions_updated_at` (fungsi `set_updated_at()`) memperbarui `transactions.updated_at` pada setiap UPDATE - pengganti `ON UPDATE CURRENT_TIMESTAMP` milik MySQL.
- Seed: 3 kategori (`Voucher Game`, `Pulsa`, `Token Listrik`) dan 9 produk aktif (3 per kategori). Produk voucher belum punya kode apa pun - stok baru muncul setelah admin menambahkan kode lewat halaman Stok.

Relasi ringkas:

```
users 1--N transactions N--1 products N--1 categories
transactions 1--1 payments   (payments.diverifikasi_oleh -> users)
products 1--N voucher_stocks; transactions N--1 voucher_stocks (id_stok)
```
## Arsitektur & Alur Request

Aplikasi memakai pola **front controller** sederhana: semua request masuk lewat `index.php` dengan parameter `?page=xxx`.

1. `index.php` memuat `config.php`, `app/helpers.php`, `app/repositories.php`, dan ketiga file aksi di `app/actions/`.
2. Nilai `$_GET['page']` (default `home`) diverifikasi terhadap **whitelist** `$routes`: `home, catalog, product, login, register, logout, profile, orders, order, checkout, admin, products, categories, stock, payments, users, reports`. Nilai di luar whitelist jatuh kembali ke `home`.
3. Jika metode request `POST`:
   - `check_csrf()` memvalidasi token `_csrf` (HTTP 419 bila gagal).
   - Handler dicoba berurutan: `handle_auth_action()` -> `handle_customer_action()` -> `handle_admin_action()`. Jika ketiganya mengembalikan `false`, dilempar `RuntimeException('Aksi tidak dikenal.')`.
   - Blok `catch (Throwable)`: rollback transaksi PDO bila masih terbuka, `flash()` pesan error (PDOException diganti pesan generik "Data gagal disimpan. Pastikan email, nomor HP, atau kode tidak duplikat."), lalu `go()` kembali ke `?page=...` - ditambah `&id=` bila ada `$_POST['id_transaksi']`.
4. Data halaman diambil lewat fungsi baca di `app/repositories.php`; `index.php` menyiapkan `$cats = all_categories($pdo)` untuk dipakai semua halaman.
5. `$flash = take_flash()` mengambil sekaligus menghapus pesan satu-kali-tampil dari sesi.
6. `views/layout.php` merender kerangka HTML: topbar + navigasi sesuai peran, toast flash, `<main>` yang men-require `views/pages/{page}.php` (fallback ke `home.php` bila file tidak ada), footer, lalu memuat `assets/app.js`.

Halaman GET juga menjalankan proteksi aksesnya sendiri: halaman pelanggan (`checkout`, `orders`, `profile`) memanggil `require_login()` di baris pertama; halaman admin (`admin`, `products`, `categories`, `stock`, `payments`, `users`, `reports`) memanggil `require_admin()`.

## Struktur Direktori

```
topupku-native/
|-- index.php              # Front controller: whitelist rute, dispatch POST, render layout
|-- config.php             # Konstanta, koneksi PDO, sesi, CSRF, flash, upload, guard peran
|-- create-admin.php       # CLI pembuat akun admin
|-- database.sql           # Skema PostgreSQL + trigger + data awal
|-- .htaccess              # Options -Indexes + blokir config.php, database.sql, README.md, create-admin.php
|-- app/
|   |-- helpers.php        # Helper tampilan, konstanta status, ikon SVG
|   |-- repositories.php   # Semua query baca + generator kode transaksi
|   `-- actions/
|       |-- auth.php       # handle_auth_action: register, login, logout
|       |-- customer.php   # handle_customer_action: order, checkout, profile, batal pesanan
|       `-- admin.php      # handle_admin_action + 6 handler admin
|-- views/
|   |-- layout.php         # Kerangka HTML: topbar, nav per peran, toast, main, footer
|   |-- products-grid.php  # Partial grid kartu produk (dipakai home & catalog)
|   `-- pages/             # 15 halaman, satu file per nilai ?page=
|       |-- home.php       # Beranda: produk unggulan, kategori, keunggulan
|       |-- catalog.php    # Katalog: pencarian + filter kategori
|       |-- product.php    # Detail produk + form pemesanan
|       |-- login.php      # Form masuk
|       |-- register.php   # Form daftar
|       |-- checkout.php   # Instruksi bayar + form unggah bukti + pilih metode
|       |-- orders.php     # Riwayat pesanan + filter status + tombol batal
|       |-- profile.php    # Profil + ganti kata sandi
|       |-- admin.php      # Dashboard admin: statistik + transaksi terbaru
|       |-- products.php   # Kelola produk (form CRUD + tabel + unggah gambar)
|       |-- categories.php # Kelola kategori
|       |-- stock.php      # Tambah kode voucher + tabel stok & kode terbaru
|       |-- payments.php   # Verifikasi pembayaran + proses/selesaikan pesanan
|       |-- users.php      # Kelola status akun pelanggan
|       `-- reports.php    # Laporan periode: ringkasan, terlaris, rincian
|-- assets/
|   |-- style.css          # Design system "kios biru powder"
|   |-- app.js             # Auto-hapus toast + guard ukuran file 5 MB
|   `-- product.svg        # Gambar placeholder produk
`-- uploads/               # Bukti bayar & gambar produk (+ .htaccess anti-eksekusi PHP)
```
## Referensi Fungsi Lengkap

### a) config.php

Konstanta dan koneksi dijelaskan di bagian [Konfigurasi](#konfigurasi). Sembilan fungsi utilitas:

| Fungsi | Keterangan |
| --- | --- |
| `go(string $url): never` | Redirect via header `Location` lalu `exit`. Dipakai semua handler setelah selesai dan oleh guard saat menolak akses. |
| `csrf(): string` | Mengembalikan token CSRF dari sesi; membuat `$_SESSION['_csrf'] = bin2hex(random_bytes(32))` bila belum ada. Dipanggil setiap form POST di view. |
| `check_csrf(): void` | Membandingkan `$_POST['_csrf']` dengan token sesi memakai `hash_equals()`. Bila beda/kosong: HTTP 419 + "Sesi formulir kedaluwarsa. Muat ulang halaman." Dipanggil sekali di `index.php` untuk semua POST. |
| `flash(string $msg, string $type = 'success'): void` | Menyimpan pesan satu-kali-tampil `[$msg, $type]` ke `$_SESSION['_flash']`. `$type` berisi `success` atau `error` (menentukan gaya toast). |
| `take_flash(): ?array` | Mengembalikan isi flash lalu menghapusnya dari sesi (pola read-once). Dipanggil `index.php` sebelum render. |
| `user(): ?array` | Data user sesi (`id_user`, `nama`, `email`, `role`) atau `null` untuk tamu. |
| `require_login(): void` | Tanpa sesi login: `flash('Silakan masuk untuk melanjutkan.', 'error')` + redirect ke `?page=login`. |
| `require_admin(): void` | Memanggil `require_login()` dulu, lalu bila `role !== 'admin'`: HTTP 403 "Akses khusus admin." |
| `upload_file(string $field, array $allowed, int $max = 5000000): ?string` | Memproses `$_FILES[$field]`. Mengembalikan `null` bila tidak ada file diunggah. Error upload atau ukuran > `$max` (default 5.000.000 byte = 5 MB) melempar RuntimeException. MIME file dicek `finfo(FILEINFO_MIME_TYPE)` terhadap map whitelist `$allowed` (mis. `['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']`); ekstensi diambil dari map tersebut. Nama file acak `bin2hex(random_bytes(16)) . '.' . ext`, folder `UPLOAD_DIR` dibuat otomatis (0755, rekursif). Mengembalikan nama file baru untuk disimpan ke database. |

### b) app/helpers.php

Konstanta:

| Konstanta | Nilai | Dipakai untuk |
| --- | --- | --- |
| `TRANSACTION_STATUSES` | `['menunggu_bayar', 'menunggu_verifikasi', 'diproses', 'berhasil', 'gagal', 'dibatalkan']` | Validasi filter status di `user_orders()` dan daftar filter halaman Pesanan |
| `ACTIONABLE_TRANSACTION_STATUSES` | `['menunggu_verifikasi', 'diproses']` | Transaksi yang masih butuh tindakan admin: `active_transactions()` dan `process_payment_action()` |
| `OPEN_TRANSACTION_STATUSES` | `['menunggu_bayar', 'menunggu_verifikasi', 'diproses']` | Hitungan "tertunda" di `sales_summary()` |
| `VOUCHER_CATEGORY` | `'Voucher Game'` | Nama kategori voucher: validasi stok, alokasi otomatis, statistik stok menipis |

Fungsi:

| Fungsi | Keterangan |
| --- | --- |
| `e(?string $v): string` | `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` - escaping wajib untuk semua output HTML. |
| `money(float|string $n): string` | Format `'Rp ' . number_format(..., 0, ',', '.')`, contoh `Rp 20.000`. |
| `product_image(?string $img): string` | `'uploads/' . rawurlencode($img)` bila produk punya gambar, selain itu `'assets/product.svg'` (placeholder). |
| `icon(string $name): string` | SVG inline `<svg class="ic" viewBox="0 0 24 24" ... stroke="currentColor">`. 18 nama tersedia: `gamepad`, `phone`, `bolt`, `ticket`, `bank`, `wallet`, `qr`, `receipt`, `clock`, `layers`, `user`, `box`, `chart`, `swap`, `search`, `shield`, `check`, `arrow`; nama tak dikenal fallback ke `ticket`. |
| `category_icon(string $categoryName): string` | Memilih ikon dari nama kategori: mengandung `Token`/`PLN`/`Listrik` -> `bolt`; `Pulsa`/`Data` -> `phone`; `Voucher`/`Game` -> `gamepad`; selain itu `ticket`. |
| `status_label(string $status): string` | Terjemahan status ke label Indonesia: `menunggu_bayar` -> "Menunggu bayar", `menunggu_verifikasi` -> "Menunggu verifikasi", `diproses` -> "Diproses", `berhasil` -> "Berhasil", `gagal` -> "Gagal", `dibatalkan` -> "Dibatalkan"; nilai lain -> `ucfirst($status)`. |
| `is_voucher_category(?string $categoryName): bool` | `true` persis saat nama kategori sama dengan `VOUCHER_CATEGORY`. Dipakai validasi stok saat order dan alokasi voucher saat finish. |
| `is_voucher_label(string $categoryName): bool` | Pencocokan longgar: nama kategori mengandung substring `Voucher`. Dipakai untuk label UI (stok, ketersediaan). |
| `destination_label(string $categoryName): string` | Label input tujuan: mengandung `Token` -> "ID pelanggan PLN"; kategori voucher -> "ID game"; selain itu "Nomor HP". |
| `availability_label(string $categoryName, int $stock): string` | Non-voucher: "Tersedia 24 jam"; voucher: "Stok tersedia" / "Stok sedang habis". |
| `product_is_orderable(string $categoryName, int $stock): bool` | Boleh dipesan bila non-voucher, atau voucher dengan `$stock > 0`. Menentukan tampil/tidaknya form pemesanan di detail produk. |
### c) app/repositories.php

Semua fungsi baca. Konstanta `PRODUCT_WITH_STOCK_SQL` = SELECT `products.*` + `categories.nama_kategori` + subquery hitung `voucher_stocks` berstatus `tersedia` sebagai kolom `stok` (JOIN `categories` via `USING(id_kategori)`) - fondasi hampir semua daftar produk.

| Fungsi | SELECT / filter | Dipakai di |
| --- | --- | --- |
| `all_categories(PDO $pdo): array` | Semua kategori urut `id_kategori` | `index.php` (variabel `$cats`, tersedia untuk semua halaman) |
| `categories_with_counts(PDO $pdo): array` | Kategori + kolom `jumlah` produk (LEFT JOIN + GROUP BY) | Halaman Kategori admin |
| `featured_products(PDO $pdo, int $limit = 6): array` | 6 produk `aktif` terbaru + stok | Beranda |
| `catalog_products(PDO $pdo, string $term, int $categoryId): array` | Produk `aktif`; `$term` dicari ke `nama_produk`/`provider` via `ILIKE '%..%'`; filter `id_kategori` bila non-zero | Halaman Katalog |
| `find_active_product(PDO $pdo, int $productId): ?array` | Satu produk `aktif` + stok; `null` bila tidak ada | Detail produk; validasi aksi order |
| `find_product(PDO $pdo, int $productId): ?array` | Satu produk apa pun (tanpa syarat status) | Form edit produk admin (`?edit=`) |
| `all_products_with_stock(PDO $pdo): array` | Semua produk + stok, urut terbaru | Tabel produk admin |
| `voucher_products(PDO $pdo): array` | Produk kategori `VOUCHER_CATEGORY` + agregat jumlah voucher `tersedia`/`terpakai` (`COUNT(*) FILTER`) | Halaman Stok admin |
| `recent_vouchers(PDO $pdo, int $limit = 80): array` | 80 kode voucher terbaru + `nama_produk` | Halaman Stok admin |
| `find_user(PDO $pdo, int $userId): ?array` | Satu user berdasarkan `id_user` | Halaman Profil |
| `find_user_transaction(PDO $pdo, int $transactionId, int $userId): ?array` | Transaksi milik user tertentu + data produk (sekaligus guard kepemilikan) | Halaman Checkout |
| `user_orders(PDO $pdo, int $userId, string $status = ''): array` | Semua transaksi user + data produk; filter status bila nilainya ada di `TRANSACTION_STATUSES` | Halaman Pesanan |
| `latest_transactions(PDO $pdo, int $limit = 6): array` | 6 transaksi terbaru + nama user & produk | Dashboard admin |
| `active_transactions(PDO $pdo): array` | Transaksi berstatus `ACTIONABLE_TRANSACTION_STATUSES` + pembayaran (`metode`, `bukti_bayar`), urut terlama dulu | Halaman Transaksi admin |
| `customers_with_stats(PDO $pdo): array` | User `role = 'pelanggan'` + hitung transaksi masing-masing | Halaman Pengguna admin |
| `admin_stats(PDO $pdo): array` | Statistik dashboard, key: `today_count`, `today_omzet` (transaksi `berhasil` hari ini), `pending` (menunggu verifikasi), `low_stock` (produk voucher aktif dengan stok tersedia < 5), `customers` | Dashboard admin |
| `sales_summary(PDO $pdo, string $from, string $to): array` | Periode `DATE(created_at) BETWEEN`: `total` transaksi, `omzet` (SUM `total_bayar` status `berhasil`), `tertunda` (status di `OPEN_TRANSACTION_STATUSES`) | Halaman Laporan |
| `best_sellers(PDO $pdo, string $from, string $to, int $limit = 5): array` | 5 produk terlaris periode (status `berhasil`) + `jumlah` + `omzet` | Halaman Laporan |
| `transactions_between(PDO $pdo, string $from, string $to): array` | Semua transaksi periode + nama user & produk | Halaman Laporan |
| `create_order_code(PDO $pdo): string` | Generator kode `TK` + `date('ymd')` + 6 karakter hex acak uppercase; loop cek keunikan terhadap tabel `transactions` | Aksi order pelanggan |

Periode laporan bawaan halaman Laporan: `$from` default `date('Y-m-01')` (awal bulan berjalan), `$to` default `date('Y-m-d')`; keduanya divalidasi format `Y-m-d` lewat regex.

### d) app/actions/ - Handler POST

#### `handle_auth_action(PDO $pdo, string $page): bool` (auth.php)

| Aksi (`$page`) | Validasi & efek samping DB | Redirect / flash |
| --- | --- | --- |
| `register` | `nama` minimal 2 karakter, email valid (disimpan lowercase), `no_hp` digit 9-15 karakter, sandi minimal 8; INSERT `users(nama, email, no_hp, password)` - `role` default `pelanggan`, sandi di-hash `password_hash(..., PASSWORD_DEFAULT)` | Flash "Akun berhasil dibuat. Silakan masuk." -> `?page=login` |
| `login` | Cari user dengan `email` + `status = 'aktif'`; verifikasi sandi `password_verify()`; gagal -> RuntimeException "Email atau kata sandi tidak cocok." | `session_regenerate_id(true)`, isi `$_SESSION['user']` (`id_user`, `nama`, `email`, `role`); admin -> `?page=admin`, pelanggan -> `?page=home` |
| `logout` | `unset($_SESSION['user'])` + `session_regenerate_id(true)` | Redirect `?` |
| lainnya | - | `return false` (bukan aksi auth) |

#### `handle_customer_action(PDO $pdo, string $page): bool` (customer.php)

Semua branch memanggil `require_login()` terlebih dahulu.

| Aksi | Validasi & efek samping DB | Redirect / flash |
| --- | --- | --- |
| `order` (buat pesanan) | `nomor_tujuan` 4-50 karakter dan cocok regex `^[\pL\pN ._@+-]+$`; produk harus `aktif` (`find_active_product`); kategori voucher wajib stok >= 1. INSERT `transactions` berstatus `menunggu_bayar` dengan kode dari `create_order_code()` dan `total_bayar` = `harga_jual` produk | Redirect `?page=checkout&id={id transaksi baru}` |
| `checkout` (kirim bukti bayar) | Transaksi harus milik user dan masih `menunggu_bayar`; unggah bukti wajib (`upload_file` JPG/PNG/WebP); `metode` salah satu dari `transfer_bank`/`e_wallet`/`qris`. Di dalam transaksi DB: kunci baris `payments` dengan `FOR UPDATE` - bila sudah ada dan berstatus `ditolak`, baris di-update ulang ke `menunggu` (kirim ulang); bila ada dengan status lain -> ditolak ("Pembayaran sudah dikirim dan sedang diproses."); bila belum ada -> INSERT. Lalu UPDATE `transactions` -> `menunggu_verifikasi`, commit | Flash "Bukti pembayaran terkirim. Admin akan memverifikasi pesananmu." -> `?page=orders` |
| `profile` | `nama` minimal 2 karakter, `no_hp` minimal 9 digit; UPDATE `users.nama`/`no_hp` + sinkronisasi `$_SESSION['user']['nama']`; bila `password` diisi: minimal 8 karakter dan harus sama dengan `password_confirm`, UPDATE hash sandi | Flash "Profil berhasil diperbarui." -> `?page=profile` |
| `orders` (batal pesanan) | UPDATE `transactions` -> `dibatalkan` hanya bila milik user dan masih `menunggu_bayar` (dicek di klausa WHERE) | Flash "Pesanan dibatalkan." / "Pesanan tidak dapat dibatalkan." -> `?page=orders` |
| lainnya | - | `return false` |

#### `handle_admin_action(PDO $pdo, string $page): bool` (admin.php)

Dispatcher: hanya memproses `$page` di `['products', 'categories', 'stock', 'payments', 'users']` (selain itu `return false`), memanggil `require_admin()`, lalu mengeksekusi handler lewat `match`:

| Handler | Aksi | Detail |
| --- | --- | --- |
| `save_category(PDO $pdo): never` | `categories` | `nama_kategori` wajib 2-50 karakter; `id_kategori` > 0 -> UPDATE, selain itu INSERT; `deskripsi` opsional. Flash "Kategori berhasil disimpan." -> `?page=categories` |
| `save_product(PDO $pdo): never` | `products` | Wajib: `id_kategori`, `nama_produk`, `provider`, `nominal` >= 1, `harga_jual` >= 1, `status` salah satu `aktif`/`nonaktif`. Gambar opsional via `upload_file` - kolom `gambar` hanya di-UPDATE bila ada file baru. INSERT atau UPDATE sesuai ada/tidaknya `id_produk`. Flash "Produk tersimpan." -> `?page=products` |
| `add_voucher_stock(PDO $pdo): never` | `stock` | Isi `kode_voucher` di-split per baris (`preg_split('/\R/')`, trim, buang baris kosong); minimal 1 kode. Transaction DB: INSERT satu baris `voucher_stocks` per kode (status default `tersedia`; kode duplikat kena UNIQUE -> PDOException yang ditangkap `index.php`). Flash "Stok voucher berhasil ditambahkan." -> `?page=stock` |
| `process_payment_action(PDO $pdo): never` | `payments` | Inti verifikasi. Transaksi DB + `SELECT ... FOR UPDATE` baris transaksi+payment, hanya bila status masih di `ACTIONABLE_TRANSACTION_STATUSES` (mencegah race/double-aksi); tidak ketemu -> "Pesanan tidak lagi menunggu verifikasi." Tiga nilai `aksi`: `approve` (payment -> `terverifikasi`, transaksi -> `diproses`), `reject` (`catatan` wajib diisi; payment -> `ditolak` + catatan + `diverifikasi_oleh` + `waktu_verifikasi`; transaksi kembali -> `menunggu_bayar`), `finish` (delegasi ke `finish_transaction()`, hanya dari `diproses`). Nilai lain -> "Aksi tidak valid." Commit -> Flash "Status transaksi diperbarui." -> `?page=payments` |
| `finish_transaction(PDO $pdo, array $transaction): void` | (dipanggil aksi finish) | `hasil` wajib diisi; `hasil_status` = `berhasil`/`gagal`. Bila berhasil dan kategori produk === `VOUCHER_CATEGORY`: ambil satu `voucher_stocks` berstatus `tersedia` (`ORDER BY id_stok LIMIT 1 FOR UPDATE`) - kosong -> "Stok voucher kosong."; set `terpakai`, isi `hasil` transaksi dengan kode voucher, dan simpan `id_stok`. Terakhir UPDATE `transactions` -> status `berhasil`/`gagal` + kolom `hasil` + `id_stok` |
| `toggle_customer_status(PDO $pdo): never` | `users` | Menolak bila target adalah akun sendiri ("Akun admin yang sedang digunakan tidak dapat dinonaktifkan."). UPDATE `users.status` toggle `aktif` <-> `nonaktif` hanya untuk `role = 'pelanggan'` (user nonaktif tidak bisa login). Flash "Status pengguna diperbarui." -> `?page=users` |
## Alur Bisnis Transaksi

```
[Pelanggan] pilih produk   -> POST order     -> menunggu_bayar      (kode TK..., total = harga_jual)
[Pelanggan] unggah bukti   -> POST checkout  -> menunggu_verifikasi (payments: menunggu)
[Admin]     approve                          -> payment terverifikasi -> diproses
[Admin]     reject                           -> payment ditolak (+catatan) -> kembali ke menunggu_bayar
                                               (pelanggan bisa kirim ulang bukti atau batalkan)
[Admin]     finish (berhasil)                -> voucher dialokasikan otomatis (hasil = kode voucher, id_stok terisi) -> berhasil
[Admin]     finish (gagal)                   -> hasil = catatan admin -> gagal
[Pelanggan] batal                             -> hanya dari menunggu_bayar -> dibatalkan
```

Ringkasan tanggung jawab:

- **Pelanggan**: membuat pesanan, mengunggah / mengirim ulang bukti bayar, membatalkan pesanan yang masih `menunggu_bayar`, mengelola profil dan kata sandi.
- **Admin**: mengelola kategori, produk (+ gambar), stok voucher; memverifikasi pembayaran (approve/reject); menyelesaikan pesanan (finish dengan hasil berhasil/gagal + alokasi voucher otomatis); menonaktifkan/mengaktifkan akun pelanggan; melihat laporan periode.

## Siklus Status Transaksi

Badge status memakai kelas CSS `status status-{status}` yang didefinisikan di `assets/style.css`; label teks berasal dari `status_label()`.

| Status | Label UI | Dipicu oleh | Warna badge |
| --- | --- | --- | --- |
| `menunggu_bayar` | Menunggu bayar | Order baru dibuat; atau admin menolak bukti (reject) | Kuning (`--wait-bg` `#fbf3da`, teks `--wait-fg` `#77570f`) |
| `menunggu_verifikasi` | Menunggu verifikasi | Pelanggan mengirim bukti pembayaran | Kuning (sama dengan di atas) |
| `diproses` | Diproses | Admin menyetujui pembayaran (approve) | Biru (`--proc-bg` `#e7f0fb`, teks `--proc-fg` `#2d5c8f`) |
| `berhasil` | Berhasil | Admin menyelesaikan pesanan dengan `hasil_status=berhasil` | Hijau (`--ok-bg` `#e8f4d9`, teks `--ok-fg` `#38591a`) |
| `gagal` | Gagal | Admin menyelesaikan pesanan dengan `hasil_status=gagal` | Merah (`--fail-bg` `#fdeeec`, teks `--fail-fg` `#93392e`) |
| `dibatalkan` | Dibatalkan | Pelanggan membatalkan saat masih `menunggu_bayar` | Merah (sama dengan di atas) |

Status pembayaran pada tabel `payments` berjalan paralel: `menunggu` -> `terverifikasi` (approve) atau `ditolak` (reject, dengan `catatan`; bukti boleh dikirim ulang).

## Peran & Hak Akses

| Peran | Halaman yang bisa diakses (GET) | Navigasi topbar |
| --- | --- | --- |
| Tamu (belum login) | `home`, `catalog`, `product`, `login`, `register` | Produk, Masuk, Daftar gratis |
| Pelanggan | + `orders`, `order`, `checkout`, `profile`, `logout` | Produk, Pesanan, Profil, Keluar |
| Admin | + `admin`, `products`, `categories`, `stock`, `payments`, `users`, `reports` | Produk, Dashboard, Katalog admin, Kategori, Stok, Transaksi, Profil, Keluar |

Proteksi berlapis: handler POST pelanggan memanggil `require_login()`, handler POST admin lewat `require_admin()`, dan halaman GET pelanggan/admin juga memanggil guard yang sama di baris pertama view. Login ditolak untuk akun `status = 'nonaktif'`.

## Keamanan

- **CSRF**: semua form POST membawa token `csrf()` dan diverifikasi `check_csrf()` (perbandingan timing-safe `hash_equals`, gagal = HTTP 419).
- **XSS**: semua output view di-escape dengan `e()` (`htmlspecialchars` ENT_QUOTES, UTF-8).
- **Password**: `password_hash()` / `password_verify()` (bcrypt bawaan); minimal 8 karakter saat daftar, ganti sandi, dan buat admin.
- **SQL injection**: semua query berparameter memakai prepared statement PDO dengan `PDO::ATTR_EMULATE_PREPARES => false`.
- **Sesi**: cookie `httponly` + `samesite=Lax`; `session_regenerate_id(true)` saat login dan logout.
- **Upload**: whitelist MIME via `finfo` (hanya JPEG/PNG/WebP), maksimum 5 MB, nama file acak 32 karakter hex (mencegah path traversal dan tabrakan nama), folder `UPLOAD_DIR` dibuat otomatis 0755.
- **uploads/.htaccess**: `Options -Indexes` + menolak semua file `.php`/`.phtml`/`.phar` (unggahan tidak bisa dieksekusi).
- **Root .htaccess**: `Options -Indexes` + memblokir akses web ke `config.php`, `database.sql`, `README.md`, dan `create-admin.php` (script terakhir hanya boleh dari CLI).
- **Whitelist rute**: nilai `?page` di luar `$routes` jatuh ke `home` - tidak ada include file arbitrer.
- **Kepemilikan data**: query transaksi selalu menyaring `id_user` sesi (mis. `find_user_transaction()`), dan status transaksi dicek ulang di klausa WHERE saat setiap aksi.
- **Race condition**: `beginTransaction()` + `SELECT ... FOR UPDATE` pada checkout dan `process_payment_action()` mencegah bukti dikirim ganda atau transaksi diproses dua kali secara bersamaan.

## Frontend & Desain

- Satu stylesheet `assets/style.css` dengan design system **"kios biru powder"** (identik dengan `topupku-fullstack`): warna sinyal `--signal: #b7d3f8` dan tinta `--ink: #101e38`, font display **Space Grotesk** + font teks **DM Sans** (Google Fonts).
- Motif visual artefak **struk/voucher**: header struk ("BUKTI TOP-UP", "STRUK TOPUP"), tepi bergerigi, kartu produk, dan badge status berwarna.
- Ikon SVG inline konsisten lewat helper `icon()` dan `category_icon()` (stroke `currentColor`, kelas `ic`).
- `views/layout.php` adalah kerangka (topbar, nav per peran, toast flash, main, footer); `views/pages/*.php` berisi isi tiap halaman; `views/products-grid.php` partial grid kartu produk yang dipakai `home.php` dan `catalog.php`.
- `assets/app.js` perannya kecil: (1) auto-hapus elemen `[data-toast]` (pesan flash) setelah 4,2 detik; (2) guard sisi klien untuk input file > 5 MB (alert + reset input) - validasi sesungguhnya tetap di `upload_file()`.
- `assets/product.svg` gambar placeholder produk (dipakai `product_image()`).
- Responsif (meta viewport, layout fluid) dan sepenuhnya berbahasa Indonesia.

## Akun Admin

Tidak ada admin bawaan dari seed database. Buat lewat CLI dari folder proyek:

```bash
php create-admin.php admin@topupku.id 'PasswordMinimal8Karakter'
```

- Email default `admin@topupku.id` bila argumen pertama dikosongkan; argumen kedua adalah sandi (wajib minimal 8 karakter, ditentukan sendiri saat pembuatan).
- Validasi: email harus format valid dan belum terdaftar; selain itu script berhenti dengan pesan error di STDERR.
- Akun dibuat dengan nama `Administrator`, `no_hp` `080000000000`, `role` `admin`, `status` `aktif`, sandi di-hash `password_hash()`.
- Script hanya berjalan dari CLI (akses HTTP -> 403).

## Troubleshooting

| Gejala | Penyebab & solusi |
| --- | --- |
| "Database belum siap..." (HTTP 500) | Database/kredensial belum siap. Jalankan `createdb topupku`, impor `psql -d topupku -f database.sql`, lalu samakan `DB_HOST`/`DB_PORT`/`DB_NAME`/`DB_USER`/`DB_PASS` di `config.php` dengan lingkunganmu. |
| `could not find driver` / PDO pgsql tidak ada | Aktifkan ekstensi `pdo_pgsql` di `php.ini` (XAMPP: hapus `;` pada `extension=pdo_pgsql`), lalu restart Apache/PHP. |
| Upload selalu gagal "Format file tidak didukung" | Ekstensi `fileinfo` belum aktif (`extension=fileinfo`), atau file bukan JPEG/PNG/WebP. |
| Gagal menyimpan bukti/gambar | Pastikan proses PHP/Apache punya izin **tulis** ke `uploads/` (di Linux: `chmod -R 775 uploads` atau sesuaikan owner). |
| HTTP 419 "Sesi formulir kedaluwarsa" | Token CSRF sesi berubah/berakhir - muat ulang halaman lalu kirim ulang form. |
| "Data gagal disimpan. Pastikan email, nomor HP, atau kode tidak duplikat." | Pelanggaran UNIQUE: email/no_hp saat daftar, atau kode voucher ganda saat tambah stok. Gunakan nilai lain. |
| Produk voucher tidak bisa dipesan ("Produk tidak tersedia") | Stok voucher habis - tambahkan kode lewat menu Stok admin. |
| Halaman admin 403 "Akses khusus admin." | Akun bukan admin atau belum login; cek kolom `role` di tabel `users`. |
| Login gagal padahal sandi benar | Akun kemungkinan dinonaktifkan admin (`status = 'nonaktif'`) - login hanya menerima `status = 'aktif'`. |

---

Dokumentasi ini menggambarkan kode pada folder ini apa adanya: nama fungsi, kolom, dan nilai status diverifikasi langsung terhadap `config.php`, `app/helpers.php`, `app/repositories.php`, `app/actions/*.php`, `database.sql`, dan `views/`.