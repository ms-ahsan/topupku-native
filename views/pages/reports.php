<?php
require_admin();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date('Y-m-d');
}
$summary = sales_summary($pdo, $from, $to);
$best = best_sellers($pdo, $from, $to);
$items = transactions_between($pdo, $from, $to);
?>
<section class="admin-head">
    <div>
        <h1>Laporan penjualan.</h1>
        <p>Pantau transaksi dan omzet berdasarkan periode.</p>
    </div>
</section>
<form class="report-filter" method="get">
    <input type="hidden" name="page" value="reports">
    <label>Dari tanggal<input type="date" name="from" value="<?= e($from) ?>"></label>
    <label>Sampai tanggal<input type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="btn btn-dark">Terapkan</button>
</form>
<section class="stat-grid">
    <article class="stat-card stat-dark">
        <span>OMZET BERHASIL</span>
        <b><?= money($summary['omzet']) ?></b>
        <small>Dalam periode terpilih</small>
    </article>
    <article class="stat-card">
        <span>TOTAL TRANSAKSI</span>
        <b><?= (int) $summary['total'] ?></b>
        <small>Semua status</small>
    </article>
    <article class="stat-card">
        <span>TRANSAKSI TERTUNDA</span>
        <b><?= (int) $summary['tertunda'] ?></b>
        <small>Butuh tindak lanjut</small>
    </article>
</section>
<div class="admin-columns">
    <section class="panel">
        <h2>Produk terlaris</h2>
        <?php if (!$best): ?>
            <p>Belum ada transaksi berhasil pada periode ini.</p>
        <?php endif; ?>
        <?php foreach ($best as $i => $b): ?>
            <div class="stock-line">
                <span><b><?= $i + 1 ?></b> <?= e($b['nama_produk']) ?></span>
                <b><?= (int) $b['jumlah'] ?> terjual / <?= money($b['omzet']) ?></b>
            </div>
        <?php endforeach; ?>
    </section>
    <section class="panel">
        <h2>Rincian transaksi</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>KODE</th><th>PELANGGAN</th><th>PRODUK</th><th>TOTAL</th><th>STATUS</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $t): ?>
                        <tr>
                            <td><?= e($t['kode_transaksi']) ?></td>
                            <td><?= e($t['nama']) ?></td>
                            <td><?= e($t['nama_produk']) ?></td>
                            <td><?= money($t['total_bayar']) ?></td>
                            <td><?= status_label($t['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
