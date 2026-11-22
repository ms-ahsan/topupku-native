<?php if (!$products): ?>
    <div class="empty">Belum ada produk yang cocok.</div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
            <a class="product-card" href="?page=product&id=<?= $p['id_produk'] ?>">
                <div class="product-visual">
                    <img src="<?= e(product_image($p['gambar'])) ?>" alt="">
                    <span><?= e($p['nama_kategori']) ?></span>
                    <?php if (is_voucher_label($p['nama_kategori']) && (int) $p['stok'] < 1): ?>
                        <label class="soldout">Stok habis</label>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <small><?= e($p['provider']) ?></small>
                    <h3><?= e($p['nama_produk']) ?></h3>
                    <div class="product-bottom">
                        <b><?= money($p['harga_jual']) ?></b>
                        <span><?= icon('arrow') ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
