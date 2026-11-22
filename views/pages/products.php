<?php
require_admin();
$edit = isset($_GET['edit']) ? find_product($pdo, (int) $_GET['edit']) : null;
$rows = all_products_with_stock($pdo);
?>
<section class="admin-head">
    <div>
        <h1>Kelola produk.</h1>
        <p>Atur harga, detail dan ketersediaan katalog.</p>
    </div>
</section>
<div class="admin-columns">
    <form class="panel" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
        <input type="hidden" name="id_produk" value="<?= $edit['id_produk'] ?? '' ?>">
        <h2><?= $edit ? 'Ubah produk' : 'Tambah produk' ?></h2>
        <label>Kategori
            <select name="id_kategori" required>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id_kategori'] ?>" <?= (int) ($edit['id_kategori'] ?? 0) === (int) $c['id_kategori'] ? 'selected' : '' ?>><?= e($c['nama_kategori']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nama produk<input name="nama_produk" required value="<?= e($edit['nama_produk'] ?? '') ?>"></label>
        <label>Provider<input name="provider" required value="<?= e($edit['provider'] ?? '') ?>"></label>
        <div class="form-row">
            <label>Nominal<input type="number" min="1" name="nominal" required value="<?= e((string) ($edit['nominal'] ?? '')) ?>"></label>
            <label>Harga jual (Rp)<input type="number" min="1" name="harga_jual" required value="<?= e((string) ($edit['harga_jual'] ?? '')) ?>"></label>
        </div>
        <label>Deskripsi<textarea name="deskripsi" rows="3"><?= e($edit['deskripsi'] ?? '') ?></textarea></label>
        <label>Gambar produk<input type="file" name="gambar" accept="image/png,image/jpeg,image/webp"></label>
        <label>Status
            <select name="status">
                <option value="aktif">Aktif</option>
                <option value="nonaktif" <?= ($edit['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </label>
        <button class="btn btn-primary">Simpan produk</button>
        <?php if ($edit): ?> <a class="link-button" href="?page=products">Batal</a><?php endif; ?>
    </form>
    <div class="panel">
        <h2>Daftar produk</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>PRODUK</th><th>KATEGORI</th><th>HARGA</th><th>STOK</th><th>AKSI</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $p): ?>
                        <tr>
                            <td><b><?= e($p['nama_produk']) ?></b><small class="table-sub"><?= e($p['provider']) ?> / <?= e($p['status']) ?></small></td>
                            <td><?= e($p['nama_kategori']) ?></td>
                            <td><?= money($p['harga_jual']) ?></td>
                            <td><?= (int) $p['stok'] ?></td>
                            <td><a href="?page=products&edit=<?= $p['id_produk'] ?>">Ubah</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
