<?php $p = find_active_product($pdo, (int) ($_GET['id'] ?? 0)); ?>
<?php if (!$p): ?>
    <div class="empty">Produk tidak ditemukan.</div>
<?php else: ?>
<div class="breadcrumb"><a href="?">Beranda</a> / <a href="?page=catalog">Katalog</a> / <?= e($p['nama_produk']) ?></div>
<section class="product-detail">
    <div class="detail-image"><img src="<?= e(product_image($p['gambar'])) ?>" alt=""></div>
    <div class="detail-copy">
        <span class="detail-crumb"><?= e($p['nama_kategori']) ?> / <?= e($p['provider']) ?></span>
        <h1><?= e($p['nama_produk']) ?></h1>
        <p><?= e($p['deskripsi']) ?></p>
        <div class="detail-price"><?= money($p['harga_jual']) ?><small>Harga sudah termasuk biaya layanan</small></div>
        <div class="availability"><?= availability_label($p['nama_kategori'], (int) $p['stok']) ?></div>
        <?php if (product_is_orderable($p['nama_kategori'], (int) $p['stok'])): ?>
            <?php if (user()): ?>
                <form method="post" action="?page=order" class="order-form">
                    <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                    <label><?= destination_label($p['nama_kategori']) ?>
                        <input name="nomor_tujuan" required minlength="4" maxlength="50" placeholder="Masukkan nomor tujuan / ID akun">
                    </label>
                    <button class="btn btn-primary full">Lanjutkan pesanan</button>
                </form>
            <?php else: ?>
                <a class="btn btn-primary full" href="?page=login">Masuk untuk membeli</a>
            <?php endif; ?>
        <?php endif; ?>
        <div class="safe-note"><?= icon('check') ?> Transaksi aman &amp; dipantau sampai selesai</div>
    </div>
</section>
<?php endif; ?>
