<?php
require_login();
$status = $_GET['status'] ?? '';
$orders = user_orders($pdo, (int) user()['id_user'], $status);
?>
<section class="page-head compact">
    <h1>Semua pesanan.</h1>
    <p>Periksa status dan hasil transaksi kapan saja.</p>
</section>
<section class="catalog-wrap">
    <div class="filter-row">
        <a class="filter-pill <?= $status === '' ? 'active' : '' ?>" href="?page=orders">Semua</a>
        <?php foreach (TRANSACTION_STATUSES as $s): ?>
            <a class="filter-pill <?= $status === $s ? 'active' : '' ?>" href="?page=orders&status=<?= $s ?>"><?= status_label($s) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if (!$orders): ?>
        <div class="empty">Belum ada transaksi di sini. <a href="?page=catalog">Mulai belanja</a></div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $o): ?>
                <article class="order-card">
                    <img src="<?= e(product_image($o['gambar'])) ?>" alt="">
                    <div class="order-main">
                        <small class="order-code"><?= e($o['kode_transaksi']) ?></small>
                        <b><?= e($o['nama_produk']) ?></b>
                        <span>Tujuan <?= e($o['nomor_tujuan']) ?> (<?= money($o['total_bayar']) ?>)</span>
                        <span><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></span>
                    </div>
                    <span class="status status-<?= e($o['status']) ?>"><?= status_label($o['status']) ?></span>
                    <?php if ($o['status'] === 'menunggu_bayar'): ?>
                        <a class="btn btn-outline small-btn" href="?page=checkout&id=<?= $o['id_transaksi'] ?>">Bayar</a>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="id_transaksi" value="<?= $o['id_transaksi'] ?>">
                            <button class="link-button">Batalkan</button>
                        </form>
                    <?php elseif ($o['hasil']): ?>
                        <div class="order-result"><small>HASIL</small><b><?= e($o['hasil']) ?></b></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
