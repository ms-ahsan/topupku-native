<?php
require_admin();
$rows = voucher_products($pdo);
$codes = recent_vouchers($pdo);
?>
<section class="admin-head">
    <div>
        <h1>Stok voucher.</h1>
        <p>Setiap kode hanya diberikan sekali saat transaksi diproses.</p>
    </div>
</section>
<div class="admin-columns">
    <form class="panel" method="post">
        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
        <h2>Tambah kode voucher</h2>
        <label>Produk
            <select name="id_produk" required>
                <?php foreach ($rows as $p): ?>
                    <option value="<?= (int) $p['id_produk'] ?>"><?= e($p['nama_produk']) ?> (<?= (int) $p['tersedia'] ?> tersedia)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Daftar kode
            <textarea name="kode_voucher" rows="8" required placeholder="Satu kode per baris"></textarea>
            <small>Duplikasi kode akan ditolak oleh database.</small>
        </label>
        <button class="btn btn-primary">Simpan stok</button>
        <h3 class="subheading">Ringkasan stok</h3>
        <?php foreach ($rows as $p): ?>
            <div class="stock-line"><span><?= e($p['nama_produk']) ?></span><b><?= (int) $p['tersedia'] ?> tersedia</b></div>
        <?php endforeach; ?>
    </form>
    <div class="panel">
        <h2>Kode voucher terakhir</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>PRODUK</th><th>KODE</th><th>STATUS</th><th>DITAMBAHKAN</th></tr></thead>
                <tbody>
                    <?php foreach ($codes as $v): ?>
                        <tr>
                            <td><?= e($v['nama_produk']) ?></td>
                            <td><code><?= e($v['kode_voucher']) ?></code></td>
                            <td><?= e($v['status']) ?></td>
                            <td><?= date('d M Y', strtotime($v['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
