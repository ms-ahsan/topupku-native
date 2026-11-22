<?php
require_admin();
$rows = categories_with_counts($pdo);
?>
<section class="admin-head">
    <div>
        <h1>Kelola kategori.</h1>
        <p>Kategori digunakan untuk mengelompokkan dan memfilter katalog pelanggan.</p>
    </div>
    <a class="btn btn-outline" href="?page=products">Kelola produk</a>
</section>
<div class="admin-columns">
    <form class="panel" method="post">
        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
        <h2>Tambah kategori</h2>
        <label>Nama kategori<input name="nama_kategori" required maxlength="50" placeholder="Contoh: Voucher Game"></label>
        <label>Deskripsi<input name="deskripsi_kategori" maxlength="255" placeholder="Keterangan singkat"></label>
        <button class="btn btn-primary">Simpan kategori</button>
    </form>
    <div class="panel">
        <h2>Daftar kategori</h2>
        <?php foreach ($rows as $c): ?>
            <form class="stock-line" method="post" style="align-items:end;flex-wrap:wrap">
                <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                <input type="hidden" name="id_kategori" value="<?= $c['id_kategori'] ?>">
                <label style="flex:1;min-width:140px">Nama<input name="nama_kategori" required maxlength="50" value="<?= e($c['nama_kategori']) ?>"></label>
                <label style="flex:1;min-width:160px">Deskripsi<input name="deskripsi_kategori" maxlength="255" value="<?= e($c['deskripsi']) ?>"></label>
                <span style="font-size:12px"><?= (int) $c['jumlah'] ?> produk</span>
                <button class="btn btn-outline">Simpan</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
