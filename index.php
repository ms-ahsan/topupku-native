<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/repositories.php';
require __DIR__ . '/app/actions/auth.php';
require __DIR__ . '/app/actions/customer.php';
require __DIR__ . '/app/actions/admin.php';

$routes = [
    'home', 'catalog', 'product', 'login', 'register', 'logout', 'profile',
    'orders', 'order', 'checkout', 'admin', 'products', 'categories',
    'stock', 'payments', 'users', 'reports',
];
$page = $_GET['page'] ?? 'home';
if (!in_array($page, $routes, true)) {
    $page = 'home';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        if (!handle_auth_action($pdo, $page)
            && !handle_customer_action($pdo, $page)
            && !handle_admin_action($pdo, $page)) {
            throw new RuntimeException('Aksi tidak dikenal.');
        }
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $error instanceof PDOException
            ? 'Data gagal disimpan. Pastikan email, nomor HP, atau kode tidak duplikat.'
            : $error->getMessage();
        flash($message, 'error');
        $url = '?page=' . urlencode($page);
        if (!empty($_POST['id_transaksi'])) {
            $url .= '&id=' . (int) $_POST['id_transaksi'];
        }
        go($url);
    }
}

$cats = all_categories($pdo);
$flash = take_flash();
require __DIR__ . '/views/layout.php';
