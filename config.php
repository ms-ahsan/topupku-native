<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
date_default_timezone_set('Asia/Jakarta');

const DB_HOST = '127.0.0.1';
const DB_PORT = '5432';
const DB_NAME = 'topupku';
const DB_USER = 'postgres';
const DB_PASS = '';
const APP_NAME = 'TopUpKu';
const UPLOAD_DIR = __DIR__ . '/uploads';

try {
    $pdo = new PDO('pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ";options='--client_encoding=UTF8'", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException) {
    http_response_code(500);
    exit('Database belum siap. Buat database <b>topupku</b> (mis. <code>createdb topupku</code>), impor <a href="database.sql">database.sql</a> via psql, lalu sesuaikan kredensial di config.php.');
}

function go(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function check_csrf(): void
{
    if (!hash_equals(csrf(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman.');
    }
}

function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['_flash'] = [$msg, $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $flash;
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!user()) {
        flash('Silakan masuk untuk melanjutkan.', 'error');
        go('?page=login');
    }
}

function require_admin(): void
{
    require_login();
    if (user()['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses khusus admin.');
    }
}

function upload_file(string $field, array $allowed, int $max = 5000000): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $max) {
        throw new RuntimeException('File gagal diunggah atau ukurannya terlalu besar.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = $allowed[$mime] ?? null;
    if (!$ext) {
        throw new RuntimeException('Format file tidak didukung.');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        throw new RuntimeException('Gagal menyimpan file.');
    }
    return $name;
}
