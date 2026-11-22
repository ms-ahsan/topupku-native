<?php
require_admin();
$stats = admin_stats($pdo);
$latest = latest_transactions($pdo);
?>
<section class="admin-head">
    <div>
        <h1>Ringkasan TopUpKu</h1>
        <p>Halo <?= e(user()['nama']) ?>, berikut kondisi toko hari ini.</p>
    </div>
    <a class="btn btn-primary" href="?page=payments">Lihat antrean</a>
</section>
<section class="stat-grid">
    <article class="stat-card stat-dark">
        <span>OMZET HARI INI</span>
        <b><?= money($stats['today_omzet']) ?></b>
        <small><?= $stats['today_count'] ?> transaksi berhasil</small>
        <?= icon('receipt') ?>
    </article>
    <article class="stat-card">
        <span>MENUNGGU VERIFIKASI</span>
        <b><?= $stats['pending'] ?></b>
        <small>Perlu ditinjau admin</small>
        <?= icon('clock') ?>
    </article>
    <article class="stat-card">
        <span>STOK MENIPIS</span>
        <b><?= $stats['low_stock'] ?></b>
        <small>Produk voucher di bawah 5 kode</small>
        <?= icon('layers') ?>
    </article>
    <article class="stat-card">
        <span>TOTAL PENGGUNA</span>
        <b><?= $stats['customers'] ?></b>
        <small>Akun pelanggan aktif</small>
        <?= icon('user') ?>
    </article>
</section>
<section class="admin-shortcuts">
    <a href="?page=products"><b><?= icon('box') ?></b><span>Kelola produk<small>Atur harga dan katalog</small></span></a>
    <a href="?page=stock"><b><?= icon('layers') ?></b><span>Stok voucher<small>Tambah kode voucher</small></span></a>
    <a href="?page=payments"><b><?= icon('swap') ?></b><span>Transaksi<small>Verifikasi dan proses</small></span></a>
    <a href="?page=reports"><b><?= icon('chart') ?></b><span>Laporan<small>Performa penjualan</small></span></a>
    <a href="?page=users"><b><?= icon('user') ?></b><span>Pengguna<small>Kelola akun pelanggan</small></span></a>
</section>
<section class="panel admin-table">
    <div class="section-head">
        <h2>Transaksi terbaru</h2>
        <a class="text-link" href="?page=payments">Lihat semua</a>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>KODE</th><th>PELANGGAN</th><th>PRODUK</th><th>TOTAL</th><th>STATUS</th></tr></thead>
            <tbody>
                <?php foreach ($latest as $t): ?>
                    <tr>
                        <td><?= e($t['kode_transaksi']) ?></td>
                        <td><?= e($t['nama']) ?></td>
                        <td><?= e($t['nama_produk']) ?></td>
                        <td><?= money($t['total_bayar']) ?></td>
                        <td><span class="status status-<?= e($t['status']) ?>"><?= status_label($t['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
