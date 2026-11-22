<?php $featured = featured_products($pdo); ?>
<section class="hero">
    <div class="hero-copy">
        <h1>Waktunya main,<br>top up belakangan.</h1>
        <p>Isi game, pulsa, dan token listrikmu dalam hitungan detik. Harga jelas, proses aman, selalu siap 24 jam.</p>
        <form class="hero-search" action="" method="get">
            <input type="hidden" name="page" value="catalog">
            <?= icon('search') ?>
            <input name="q" placeholder="Cari game, pulsa, atau produk…">
            <button>Temukan produk</button>
        </form>
        <div class="hero-proof">
            <span><?= icon('check') ?> Harga transparan</span>
            <span><?= icon('check') ?> Aman &amp; terpercaya</span>
            <span><?= icon('check') ?> Proses cepat</span>
        </div>
    </div>
    <div class="hero-art" aria-hidden="true">
        <div class="voucher-card">
            <small>Voucher game</small>
            <b>100</b>
            <span>Diamonds</span>
            <div class="barcode"></div>
        </div>
        <div class="struk-card">
            <div class="struk-head">BUKTI TOP-UP<?= icon('receipt') ?></div>
            <div class="struk-row"><span>Top-up game</span><b>25.000</b></div>
            <div class="struk-row"><span>Biaya layanan</span><b>1.000</b></div>
            <div class="struk-tear">
                <div class="struk-total"><span>Total</span><b>Rp 26.000</b></div>
                <span class="struk-paid">LUNAS</span>
            </div>
            <div class="barcode"></div>
        </div>
    </div>
</section>
<section class="trust-row">
    <div><?= icon('receipt') ?><span>Harga transparan<small>Tanpa biaya tersembunyi</small></span></div>
    <div><?= icon('bolt') ?><span>Proses super cepat<small>Bayar, kami langsung proses</small></span></div>
    <div><?= icon('clock') ?><span>Selalu siap 24/7<small>Belanja kapan pun kamu mau</small></span></div>
    <div><?= icon('shield') ?><span>Transaksi aman<small>Pesanan terpantau sampai selesai</small></span></div>
</section>
<section class="section">
    <div class="section-head">
        <h2>Semua kebutuhan,<br>satu tempat.</h2>
        <a class="text-link" href="?page=catalog">Jelajahi semua produk</a>
    </div>
    <div class="category-grid">
        <?php foreach ($cats as $i => $c): ?>
            <a class="category-card cat-<?= $i ?>" href="?page=catalog&category=<?= $c['id_kategori'] ?>">
                <div class="cat-icon"><?= category_icon($c['nama_kategori']) ?></div>
                <h3><?= e($c['nama_kategori']) ?></h3>
                <span><?= e($c['deskripsi']) ?></span>
                <b class="circle-arrow"><?= icon('arrow') ?></b>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<section class="section products-section">
    <div class="section-head">
        <h2>Favorit minggu ini.</h2>
        <a class="text-link" href="?page=catalog">Lihat katalog</a>
    </div>
    <?php $products = $featured; require __DIR__ . '/../products-grid.php'; ?>
</section>
<section class="bottom-banner">
    <div>
        <h2>Upgrade pengalamanmu<br>mulai dari sekarang.</h2>
    </div>
    <a class="btn btn-dark" href="?page=catalog">Mulai top up</a>
</section>
