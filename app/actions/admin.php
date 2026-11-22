<?php
declare(strict_types=1);

function handle_admin_action(PDO $pdo, string $page): bool
{
    if (!in_array($page, ['products', 'categories', 'stock', 'payments', 'users'], true)) {
        return false;
    }
    require_admin();

    match ($page) {
        'categories' => save_category($pdo),
        'products' => save_product($pdo),
        'stock' => add_voucher_stock($pdo),
        'payments' => process_payment_action($pdo),
        'users' => toggle_customer_status($pdo),
    };
    return true;
}

function save_category(PDO $pdo): never
{
    $name = trim($_POST['nama_kategori'] ?? '');
    $description = trim($_POST['deskripsi_kategori'] ?? '');
    if (strlen($name) < 2 || strlen($name) > 50) {
        throw new RuntimeException('Nama kategori harus 2–50 karakter.');
    }
    $id = (int) ($_POST['id_kategori'] ?? 0);
    if ($id) {
        $pdo->prepare('UPDATE categories SET nama_kategori = ?, deskripsi = ? WHERE id_kategori = ?')->execute([$name, $description, $id]);
    } else {
        $pdo->prepare('INSERT INTO categories(nama_kategori,deskripsi) VALUES(?,?)')->execute([$name, $description]);
    }
    flash('Kategori berhasil disimpan.');
    go('?page=categories');
}

function save_product(PDO $pdo): never
{
    $id = (int) ($_POST['id_produk'] ?? 0);
    $categoryId = (int) ($_POST['id_kategori'] ?? 0);
    $name = trim($_POST['nama_produk'] ?? '');
    $provider = trim($_POST['provider'] ?? '');
    $nominal = (int) ($_POST['nominal'] ?? 0);
    $price = (float) ($_POST['harga_jual'] ?? 0);
    $description = trim($_POST['deskripsi'] ?? '');
    $status = $_POST['status'] ?? 'aktif';
    if (!$categoryId || !$name || !$provider || $nominal < 1 || $price < 1 || !in_array($status, ['aktif', 'nonaktif'], true)) {
        throw new RuntimeException('Lengkapi data produk dengan benar.');
    }
    $image = upload_file('gambar', ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']);
    if ($id) {
        $sql = 'UPDATE products SET id_kategori = ?, nama_produk = ?, provider = ?, nominal = ?, harga_jual = ?, deskripsi = ?, status = ?' . ($image ? ', gambar = ?' : '') . ' WHERE id_produk = ?';
        $values = [$categoryId, $name, $provider, $nominal, $price, $description, $status];
        if ($image) $values[] = $image;
        $values[] = $id;
        $pdo->prepare($sql)->execute($values);
    } else {
        $pdo->prepare('INSERT INTO products(id_kategori,nama_produk,provider,nominal,harga_jual,deskripsi,gambar,status) VALUES(?,?,?,?,?,?,?,?)')->execute([$categoryId, $name, $provider, $nominal, $price, $description, $image, $status]);
    }
    flash('Produk tersimpan.');
    go('?page=products');
}

function add_voucher_stock(PDO $pdo): never
{
    $productId = (int) ($_POST['id_produk'] ?? 0);
    $codes = array_filter(
        array_map('trim', preg_split('/\R/', trim($_POST['kode_voucher'] ?? '')) ?: []),
        static fn (string $code): bool => $code !== '',
    );
    if (!$codes) {
        throw new RuntimeException('Masukkan minimal satu kode voucher.');
    }
    $pdo->beginTransaction();
    $query = $pdo->prepare('INSERT INTO voucher_stocks(id_produk,kode_voucher) VALUES(?,?)');
    foreach ($codes as $code) {
        $query->execute([$productId, $code]);
    }
    $pdo->commit();
    flash('Stok voucher berhasil ditambahkan.');
    go('?page=stock');
}

function process_payment_action(PDO $pdo): never
{
    $transactionId = (int) ($_POST['id_transaksi'] ?? 0);
    $action = $_POST['aksi'] ?? '';
    $pdo->beginTransaction();
    $actionable = implode(', ', array_fill(0, count(ACTIONABLE_TRANSACTION_STATUSES), '?'));
    $query = $pdo->prepare("SELECT t.*, p.id_pembayaran FROM transactions t JOIN payments p USING(id_transaksi) WHERE t.id_transaksi = ? AND t.status IN ($actionable) FOR UPDATE");
    $query->execute([$transactionId, ...ACTIONABLE_TRANSACTION_STATUSES]);
    $transaction = $query->fetch();
    if (!$transaction) throw new RuntimeException('Pesanan tidak lagi menunggu verifikasi.');

    if ($action === 'reject' || $action === 'approve') {
        if ($transaction['status'] !== 'menunggu_verifikasi') {
            throw new RuntimeException('Pembayaran sudah diverifikasi sebelumnya.');
        }
    } elseif ($action === 'finish' && $transaction['status'] !== 'diproses') {
        throw new RuntimeException('Pesanan belum siap diproses.');
    }

    if ($action === 'reject') {
        $reason = trim($_POST['catatan'] ?? '');
        if (!$reason) throw new RuntimeException('Alasan penolakan wajib diisi.');
        $pdo->prepare("UPDATE payments SET status = 'ditolak', catatan = ?, diverifikasi_oleh = ?, waktu_verifikasi = NOW() WHERE id_transaksi = ?")->execute([$reason, user()['id_user'], $transactionId]);
        $pdo->prepare("UPDATE transactions SET status = 'menunggu_bayar' WHERE id_transaksi = ?")->execute([$transactionId]);
    } elseif ($action === 'approve') {
        $pdo->prepare("UPDATE payments SET status = 'terverifikasi', catatan = NULL, diverifikasi_oleh = ?, waktu_verifikasi = NOW() WHERE id_transaksi = ?")->execute([user()['id_user'], $transactionId]);
        $pdo->prepare("UPDATE transactions SET status = 'diproses' WHERE id_transaksi = ?")->execute([$transactionId]);
    } elseif ($action === 'finish') {
        finish_transaction($pdo, $transaction);
    } else {
        throw new RuntimeException('Aksi tidak valid.');
    }

    $pdo->commit();
    flash('Status transaksi diperbarui.');
    go('?page=payments');
}

function finish_transaction(PDO $pdo, array $transaction): void
{
    $result = trim($_POST['hasil'] ?? '');
    $success = ($_POST['hasil_status'] ?? '') === 'berhasil';
    if (!$result) throw new RuntimeException('Hasil pesanan wajib diisi.');
    $query = $pdo->prepare('SELECT c.nama_kategori FROM products p JOIN categories c USING(id_kategori) WHERE p.id_produk = ?');
    $query->execute([$transaction['id_produk']]);
    $isVoucher = is_voucher_category((string) $query->fetchColumn());
    $stockId = null;

    if ($success && $isVoucher) {
        $query = $pdo->prepare("SELECT id_stok, kode_voucher FROM voucher_stocks WHERE id_produk = ? AND status = 'tersedia' ORDER BY id_stok LIMIT 1 FOR UPDATE");
        $query->execute([$transaction['id_produk']]);
        $stock = $query->fetch();
        if (!$stock) throw new RuntimeException('Stok voucher kosong.');
        $stockId = $stock['id_stok'];
        $result = $stock['kode_voucher'];
        $pdo->prepare("UPDATE voucher_stocks SET status = 'terpakai' WHERE id_stok = ?")->execute([$stockId]);
    }

    $pdo->prepare('UPDATE transactions SET status = ?, hasil = ?, id_stok = ? WHERE id_transaksi = ?')->execute([$success ? 'berhasil' : 'gagal', $result, $stockId, $transaction['id_transaksi']]);
}

function toggle_customer_status(PDO $pdo): never
{
    $id = (int) ($_POST['id_user'] ?? 0);
    if ($id === user()['id_user']) throw new RuntimeException('Akun admin yang sedang digunakan tidak dapat dinonaktifkan.');
    $pdo->prepare("UPDATE users SET status = CASE WHEN status = 'aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id_user = ? AND role = 'pelanggan'")->execute([$id]);
    flash('Status pengguna diperbarui.');
    go('?page=users');
}
