<?php
declare(strict_types=1);

function handle_customer_action(PDO $pdo, string $page): bool
{
    if ($page === 'order') {
        require_login();
        $productId = (int) ($_POST['id_produk'] ?? 0);
        $destination = trim($_POST['nomor_tujuan'] ?? '');
        if (strlen($destination) < 4 || strlen($destination) > 50 || !preg_match('/^[\pL\pN ._@+-]+$/u', $destination)) {
            throw new RuntimeException('Nomor tujuan tidak valid.');
        }
        $product = find_active_product($pdo, $productId);
        if (!$product || (is_voucher_category($product['nama_kategori']) && (int) $product['stok'] < 1)) {
            throw new RuntimeException('Produk tidak tersedia.');
        }
        $query = $pdo->prepare("INSERT INTO transactions(kode_transaksi,id_user,id_produk,nomor_tujuan,total_bayar,status) VALUES(?,?,?,?,?,'menunggu_bayar')");
        $query->execute([create_order_code($pdo), user()['id_user'], $productId, $destination, $product['harga_jual']]);
        go('?page=checkout&id=' . $pdo->lastInsertId());
    }

    if ($page === 'checkout') {
        require_login();
        $transactionId = (int) ($_POST['id_transaksi'] ?? 0);
        $query = $pdo->prepare("SELECT * FROM transactions WHERE id_transaksi = ? AND id_user = ? AND status = 'menunggu_bayar'");
        $query->execute([$transactionId, user()['id_user']]);
        $transaction = $query->fetch();
        if (!$transaction) {
            throw new RuntimeException('Pesanan tidak ditemukan atau sudah dibayar.');
        }

        $proof = upload_file('bukti', ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']);
        if (!$proof) {
            throw new RuntimeException('Unggah bukti pembayaran terlebih dahulu.');
        }
        $method = $_POST['metode'] ?? '';
        if (!in_array($method, ['transfer_bank', 'e_wallet', 'qris'], true)) {
            throw new RuntimeException('Metode pembayaran tidak valid.');
        }

        $pdo->beginTransaction();
        $query = $pdo->prepare('SELECT id_pembayaran, status FROM payments WHERE id_transaksi = ? FOR UPDATE');
        $query->execute([$transactionId]);
        $previousPayment = $query->fetch();
        if ($previousPayment) {
            if ($previousPayment['status'] !== 'ditolak') {
                throw new RuntimeException('Pembayaran sudah dikirim dan sedang diproses.');
            }
            $query = $pdo->prepare("UPDATE payments SET metode = ?, jumlah = ?, bukti_bayar = ?, status = 'menunggu', catatan = NULL, diverifikasi_oleh = NULL, waktu_bayar = NOW(), waktu_verifikasi = NULL WHERE id_transaksi = ?");
            $query->execute([$method, $transaction['total_bayar'], $proof, $transactionId]);
        } else {
            $query = $pdo->prepare('INSERT INTO payments(id_transaksi,metode,jumlah,bukti_bayar) VALUES(?,?,?,?)');
            $query->execute([$transactionId, $method, $transaction['total_bayar'], $proof]);
        }
        $pdo->prepare("UPDATE transactions SET status = 'menunggu_verifikasi' WHERE id_transaksi = ?")->execute([$transactionId]);
        $pdo->commit();
        flash('Bukti pembayaran terkirim. Admin akan memverifikasi pesananmu.');
        go('?page=orders');
    }

    if ($page === 'profile') {
        require_login();
        $name = trim($_POST['nama'] ?? '');
        $phone = preg_replace('/\D/', '', $_POST['no_hp'] ?? '');
        if (strlen($name) < 2 || strlen($phone) < 9) {
            throw new RuntimeException('Nama atau nomor HP tidak valid.');
        }
        $query = $pdo->prepare('UPDATE users SET nama = ?, no_hp = ? WHERE id_user = ?');
        $query->execute([$name, $phone, user()['id_user']]);
        $_SESSION['user']['nama'] = $name;

        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8 || $_POST['password'] !== ($_POST['password_confirm'] ?? '')) {
                throw new RuntimeException('Kata sandi minimal 8 karakter dan konfirmasi harus sama.');
            }
            $query = $pdo->prepare('UPDATE users SET password = ? WHERE id_user = ?');
            $query->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), user()['id_user']]);
        }
        flash('Profil berhasil diperbarui.');
        go('?page=profile');
    }

    if ($page === 'orders') {
        require_login();
        $query = $pdo->prepare("UPDATE transactions SET status = 'dibatalkan' WHERE id_transaksi = ? AND id_user = ? AND status = 'menunggu_bayar'");
        $query->execute([(int) ($_POST['id_transaksi'] ?? 0), user()['id_user']]);
        flash($query->rowCount() ? 'Pesanan dibatalkan.' : 'Pesanan tidak dapat dibatalkan.');
        go('?page=orders');
    }

    return false;
}
