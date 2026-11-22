<?php
$q = trim($_GET['q'] ?? '');
$categoryId = (int) ($_GET['category'] ?? 0);
$products = catalog_products($pdo, $q, $categoryId);
?>
<section class="page-head">
    <h1>Cari yang kamu butuhkan.</h1>
    <p>Harga jelas, tanpa kejutan di akhir.</p>
</section>
<section class="catalog-wrap">
    <form class="catalog-search" method="get">
        <input type="hidden" name="page" value="catalog">
        <input name="q" value="<?= e($q) ?>" placeholder="Cari produk atau provider">
        <button class="btn btn-dark">Cari</button>
    </form>
    <div class="filter-row">
        <a class="filter-pill <?= $categoryId === 0 ? 'active' : '' ?>" href="?page=catalog">Semua produk</a>
        <?php foreach ($cats as $c): ?>
            <a class="filter-pill <?= $categoryId === (int) $c['id_kategori'] ? 'active' : '' ?>"
               href="?page=catalog&category=<?= $c['id_kategori'] ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">
                <?= e($c['nama_kategori']) ?>
            </a>
        <?php endforeach; ?>
        <span class="results-count"><?= count($products) ?> produk</span>
    </div>
    <?php require __DIR__ . '/../products-grid.php'; ?>
</section>
