<?php
declare(strict_types=1);

const PRODUCT_WITH_STOCK_SQL =
    "SELECT p.*, c.nama_kategori, (SELECT COUNT(*) FROM voucher_stocks v WHERE v.id_produk = p.id_produk AND v.status = 'tersedia') AS stok"
    . ' FROM products p JOIN categories c USING(id_kategori)';

function all_categories(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM categories ORDER BY id_kategori')->fetchAll();
}

function categories_with_counts(PDO $pdo): array
{
    return $pdo->query('SELECT c.*, COUNT(p.id_produk) AS jumlah FROM categories c LEFT JOIN products p USING(id_kategori) GROUP BY c.id_kategori ORDER BY c.id_kategori')->fetchAll();
}

function featured_products(PDO $pdo, int $limit = 6): array
{
    $query = $pdo->prepare(PRODUCT_WITH_STOCK_SQL . " WHERE p.status = 'aktif' ORDER BY p.id_produk DESC LIMIT " . $limit);
    $query->execute();
    return $query->fetchAll();
}

function catalog_products(PDO $pdo, string $term, int $categoryId): array
{
    $sql = PRODUCT_WITH_STOCK_SQL . " WHERE p.status = 'aktif'";
    $args = [];
    if ($term !== '') {
        $sql .= ' AND (p.nama_produk ILIKE ? OR p.provider ILIKE ?)';
        $args = ['%' . $term . '%', '%' . $term . '%'];
    }
    if ($categoryId) {
        $sql .= ' AND p.id_kategori = ?';
        $args[] = $categoryId;
    }
    $sql .= ' ORDER BY p.id_produk DESC';
    $query = $pdo->prepare($sql);
    $query->execute($args);
    return $query->fetchAll();
}

function find_active_product(PDO $pdo, int $productId): ?array
{
    $query = $pdo->prepare(PRODUCT_WITH_STOCK_SQL . " WHERE p.id_produk = ? AND p.status = 'aktif'");
    $query->execute([$productId]);
    return $query->fetch() ?: null;
}

function find_product(PDO $pdo, int $productId): ?array
{
    $query = $pdo->prepare('SELECT * FROM products WHERE id_produk = ?');
    $query->execute([$productId]);
    return $query->fetch() ?: null;
}

function all_products_with_stock(PDO $pdo): array
{
    return $pdo->query(PRODUCT_WITH_STOCK_SQL . ' ORDER BY p.id_produk DESC')->fetchAll();
}

function voucher_products(PDO $pdo): array
{
    $query = $pdo->prepare("SELECT p.id_produk, p.nama_produk, c.nama_kategori,"
        . " COUNT(*) FILTER (WHERE v.status = 'tersedia') AS tersedia,"
        . " COUNT(*) FILTER (WHERE v.status = 'terpakai') AS terpakai"
        . ' FROM products p JOIN categories c USING(id_kategori) LEFT JOIN voucher_stocks v USING(id_produk)'
        . ' WHERE c.nama_kategori = ? GROUP BY p.id_produk, p.nama_produk, c.nama_kategori ORDER BY p.nama_produk');
    $query->execute([VOUCHER_CATEGORY]);
    return $query->fetchAll();
}

function recent_vouchers(PDO $pdo, int $limit = 80): array
{
    $query = $pdo->prepare('SELECT v.*, p.nama_produk FROM voucher_stocks v JOIN products p USING(id_produk) ORDER BY v.created_at DESC LIMIT ' . $limit);
    $query->execute();
    return $query->fetchAll();
}

function find_user(PDO $pdo, int $userId): ?array
{
    $query = $pdo->prepare('SELECT * FROM users WHERE id_user = ?');
    $query->execute([$userId]);
    return $query->fetch() ?: null;
}

function find_user_transaction(PDO $pdo, int $transactionId, int $userId): ?array
{
    $query = $pdo->prepare('SELECT t.*, p.nama_produk, p.provider, p.gambar FROM transactions t JOIN products p USING(id_produk) WHERE t.id_transaksi = ? AND t.id_user = ?');
    $query->execute([$transactionId, $userId]);
    return $query->fetch() ?: null;
}

function user_orders(PDO $pdo, int $userId, string $status = ''): array
{
    $sql = 'SELECT t.*, p.nama_produk, p.provider, p.gambar FROM transactions t JOIN products p USING(id_produk) WHERE t.id_user = ?';
    $args = [$userId];
    if (in_array($status, TRANSACTION_STATUSES, true)) {
        $sql .= ' AND t.status = ?';
        $args[] = $status;
    }
    $sql .= ' ORDER BY t.created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute($args);
    return $query->fetchAll();
}

function latest_transactions(PDO $pdo, int $limit = 6): array
{
    $query = $pdo->prepare('SELECT t.*, u.nama, p.nama_produk FROM transactions t JOIN users u USING(id_user) JOIN products p USING(id_produk) ORDER BY t.created_at DESC LIMIT ' . $limit);
    $query->execute();
    return $query->fetchAll();
}

function active_transactions(PDO $pdo): array
{
    $placeholders = implode(', ', array_fill(0, count(ACTIONABLE_TRANSACTION_STATUSES), '?'));
    $sql = 'SELECT t.*, u.nama, u.email, p.nama_produk, py.metode, py.bukti_bayar FROM transactions t'
        . ' JOIN users u USING(id_user) JOIN products p USING(id_produk) JOIN payments py USING(id_transaksi)'
        . " WHERE t.status IN ($placeholders) ORDER BY t.created_at ASC";
    $query = $pdo->prepare($sql);
    $query->execute(ACTIONABLE_TRANSACTION_STATUSES);
    return $query->fetchAll();
}

function customers_with_stats(PDO $pdo): array
{
    return $pdo->query("SELECT u.*, COUNT(t.id_transaksi) AS transaksi FROM users u LEFT JOIN transactions t USING(id_user) WHERE u.role = 'pelanggan' GROUP BY u.id_user ORDER BY u.created_at DESC")->fetchAll();
}

function admin_stats(PDO $pdo): array
{
    $today = $pdo->query("SELECT COUNT(*) AS n, COALESCE(SUM(total_bayar), 0) AS omzet FROM transactions WHERE DATE(created_at) = CURRENT_DATE AND status = 'berhasil'")->fetch();

    $pending = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE status = 'menunggu_verifikasi'");
    $pending->execute();

    $lowStock = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c USING(id_kategori)"
        . " WHERE p.status = 'aktif' AND c.nama_kategori = ?"
        . " AND (SELECT COUNT(*) FROM voucher_stocks v WHERE v.id_produk = p.id_produk AND v.status = 'tersedia') < 5");
    $lowStock->execute([VOUCHER_CATEGORY]);

    $customers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'pelanggan'");
    $customers->execute();

    return [
        'today_count' => (int) ($today['n'] ?? 0),
        'today_omzet' => $today['omzet'] ?? 0,
        'pending' => (int) $pending->fetchColumn(),
        'low_stock' => (int) $lowStock->fetchColumn(),
        'customers' => (int) $customers->fetchColumn(),
    ];
}

function sales_summary(PDO $pdo, string $from, string $to): array
{
    $placeholders = implode(', ', array_fill(0, count(OPEN_TRANSACTION_STATUSES), '?'));
    $query = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN status = 'berhasil' THEN total_bayar ELSE 0 END), 0) AS omzet,"
        . " COUNT(*) FILTER (WHERE status IN ($placeholders)) AS tertunda"
        . ' FROM transactions WHERE DATE(created_at) BETWEEN ? AND ?');
    $query->execute([...OPEN_TRANSACTION_STATUSES, $from, $to]);
    return $query->fetch() ?: [];
}

function best_sellers(PDO $pdo, string $from, string $to, int $limit = 5): array
{
    $query = $pdo->prepare("SELECT p.nama_produk, COUNT(*) AS jumlah, SUM(t.total_bayar) AS omzet FROM transactions t JOIN products p USING(id_produk)"
        . " WHERE t.status = 'berhasil' AND DATE(t.created_at) BETWEEN ? AND ?"
        . ' GROUP BY p.id_produk ORDER BY jumlah DESC LIMIT ' . $limit);
    $query->execute([$from, $to]);
    return $query->fetchAll();
}

function transactions_between(PDO $pdo, string $from, string $to): array
{
    $query = $pdo->prepare('SELECT t.*, u.nama, p.nama_produk FROM transactions t JOIN users u USING(id_user) JOIN products p USING(id_produk)'
        . ' WHERE DATE(t.created_at) BETWEEN ? AND ? ORDER BY t.created_at DESC');
    $query->execute([$from, $to]);
    return $query->fetchAll();
}

function create_order_code(PDO $pdo): string
{
    do {
        $code = 'TK' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        $query = $pdo->prepare('SELECT 1 FROM transactions WHERE kode_transaksi = ?');
        $query->execute([$code]);
    } while ($query->fetchColumn());

    return $code;
}
