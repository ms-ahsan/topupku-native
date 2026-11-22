<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(ucfirst($page)) ?> | TopUpKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="?"><span class="brand-mark">T</span>topup<span>ku</span></a>
    <nav>
        <a href="?page=catalog">Produk</a>
        <?php if (user()): ?>
            <?php if (user()['role'] === 'admin'): ?>
                <a href="?page=admin">Dashboard</a>
                <a href="?page=products">Katalog admin</a>
                <a href="?page=categories">Kategori</a>
                <a href="?page=stock">Stok</a>
                <a href="?page=payments">Transaksi</a>
            <?php else: ?>
                <a href="?page=orders">Pesanan</a>
            <?php endif; ?>
            <a href="?page=profile">Profil</a>
            <form method="post" action="?page=logout" class="inline">
                <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                <button class="nav-button">Keluar</button>
            </form>
        <?php else: ?>
            <a href="?page=login">Masuk</a>
            <a class="nav-cta" href="?page=register">Daftar gratis</a>
        <?php endif; ?>
    </nav>
</header>
<?php if ($flash): ?>
    <div class="toast <?= $flash[1] === 'error' ? 'error' : '' ?>" data-toast><?= e($flash[0]) ?></div>
<?php endif; ?>
<main>
<?php
$file = __DIR__ . '/pages/' . $page . '.php';
if (is_file($file)) {
    require $file;
} else {
    require __DIR__ . '/pages/home.php';
}
?>
</main>
<footer>
    <a class="brand small" href="?"><span class="brand-mark">T</span>topup<span>ku</span></a>
    <span>Top up favoritmu. Cepat, aman, kapan saja.</span>
    <span>© <?= date('Y') ?> TopUpKu</span>
</footer>
<script src="assets/app.js"></script>
</body>
</html>
