<section class="auth-layout">
    <div class="auth-aside">
        <h1>Main lagi, tanpa jeda.</h1>
        <p>Masuk untuk melanjutkan pesanan dan melihat riwayat transaksimu.</p>
        <div class="aside-receipt" aria-hidden="true">
            <div class="struk-head">STRUK TOPUP<?= icon('receipt') ?></div>
            <div class="struk-row"><span>Pulsa 10.000</span><b>11.500</b></div>
            <div class="struk-tear">
                <div class="struk-total"><span>Total</span><b>Rp 11.500</b></div>
                <span class="struk-paid">LUNAS</span>
            </div>
            <div class="barcode"></div>
        </div>
    </div>
    <form class="auth-card" method="post">
        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
        <h2>Masuk ke akun</h2>
        <p>Belum punya akun? <a href="?page=register">Daftar sekarang</a></p>
        <label>Email<input type="email" name="email" required autocomplete="email"></label>
        <label>Kata sandi<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="btn btn-primary full">Masuk</button>
        <small class="form-note">Akun admin demo: admin@topupku.id</small>
    </form>
</section>
