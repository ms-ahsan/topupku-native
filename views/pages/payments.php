<?php
require_admin();
$rows = active_transactions($pdo);
?>
<section class="admin-head">
    <div>
        <h1>Transaksi aktif.</h1>
        <p>Verifikasi pembayaran dan selesaikan pesanan pelanggan.</p>
    </div>
</section>
<?php if (!$rows): ?>
    <div class="empty">Tidak ada transaksi yang perlu diproses.</div>
<?php else: ?>
<div class="transaction-admin-list">
    <?php foreach ($rows as $t): ?>
        <article class="panel transaction-admin">
            <div class="transaction-summary">
                <div>
                    <small><b><?= e($t['kode_transaksi']) ?></b> / <?= date('d M Y, H:i', strtotime($t['created_at'])) ?></small>
                    <h2><?= e($t['nama_produk']) ?></h2>
                    <span><?= e($t['nama']) ?> / Tujuan <?= e($t['nomor_tujuan']) ?></span>
                </div>
                <b><?= money($t['total_bayar']) ?></b>
                <span class="status status-<?= e($t['status']) ?>"><?= status_label($t['status']) ?></span>
            </div>
            <?php if ($t['status'] === 'menunggu_verifikasi'): ?>
                <div class="proof-row">
                    <a target="_blank" href="uploads/<?= rawurlencode($t['bukti_bayar']) ?>">
                        <img src="uploads/<?= rawurlencode($t['bukti_bayar']) ?>" alt="Bukti pembayaran">
                    </a>
                    <span>Metode<br><b><?= e(str_replace('_', ' ', strtoupper($t['metode']))) ?></b></span>
                    <span>Pembayar<br><b><?= e($t['email']) ?></b></span>
                </div>
                <div class="decision-forms">
                    <form method="post">
                        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                        <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
                        <input type="hidden" name="aksi" value="approve">
                        <button class="btn btn-primary">Terima pembayaran</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                        <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
                        <input type="hidden" name="aksi" value="reject">
                        <input name="catatan" required placeholder="Alasan penolakan">
                        <button class="btn btn-outline">Tolak</button>
                    </form>
                </div>
            <?php else: ?>
                <form class="process-form" method="post">
                    <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
                    <input type="hidden" name="aksi" value="finish">
                    <label>Status hasil
                        <select name="hasil_status">
                            <option value="berhasil">Berhasil</option>
                            <option value="gagal">Gagal</option>
                        </select>
                    </label>
                    <label><?= str_contains($t['nama_produk'], 'PLN') ? 'Nomor token' : 'Keterangan hasil' ?>
                        <input name="hasil" required placeholder="Isi hasil pembelian">
                    </label>
                    <button class="btn btn-primary">Selesaikan pesanan</button>
                    <small>Produk voucher akan otomatis mengambil kode pertama yang tersedia.</small>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
