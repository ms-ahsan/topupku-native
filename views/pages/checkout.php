<?php
require_login();
$t = find_user_transaction($pdo, (int) ($_GET['id'] ?? 0), (int) user()['id_user']);
?>
<?php if (!$t): ?>
    <div class="empty">Pesanan tidak ditemukan.</div>
<?php else: ?>
<section class="page-head compact">
    <h1>Selesaikan pesanan.</h1>
    <p>Pesanan <?= e($t['kode_transaksi']) ?></p>
</section>
<section class="checkout-grid">
    <div class="panel">
        <div class="panel-title"><span>01</span><h2>Ringkasan pesanan</h2></div>
        <div class="summary-product">
            <img src="<?= e(product_image($t['gambar'])) ?>" alt="">
            <div>
                <small><?= e($t['provider']) ?></small>
                <b><?= e($t['nama_produk']) ?></b>
                <small>Tujuan: <?= e($t['nomor_tujuan']) ?></small>
            </div>
            <strong><?= money($t['total_bayar']) ?></strong>
        </div>
        <div class="total-row"><span>Total pembayaran</span><b><?= money($t['total_bayar']) ?></b></div>
    </div>
    <form class="panel" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
        <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
        <div class="panel-title"><span>02</span><h2>Pilih pembayaran</h2></div>
        <div class="payment-options">
            <label><input type="radio" name="metode" value="transfer_bank" checked><?= icon('bank') ?> Transfer bank</label>
            <label><input type="radio" name="metode" value="e_wallet"><?= icon('wallet') ?> E-wallet</label>
            <label><input type="radio" name="metode" value="qris"><?= icon('qr') ?> QRIS</label>
        </div>
        <div class="bank-info">
            <b>Transfer ke rekening TopUpKu</b>
            <span>Bank BCA</span>
            <span>1234567890</span>
            <span>a.n. PT TopUpKu Digital</span>
        </div>
        <label class="upload-label">Unggah bukti pembayaran
            <input type="file" name="bukti" accept="image/png,image/jpeg,image/webp" required>
            <small>JPG, PNG, atau WEBP. Maksimal 5 MB</small>
        </label>
        <button class="btn btn-primary full">Kirim bukti pembayaran</button>
        <p class="form-note">Pembayaran akan diverifikasi admin sebelum pesanan diproses.</p>
    </form>
</section>
<?php endif; ?>
