<section class="auth-layout">
    <div class="auth-aside">
        <h1>Top up gampang, hati tenang.</h1>
        <p>Buat akun untuk memesan, melacak transaksi, dan menerima hasil pembelianmu.</p>
        <div class="aside-receipt" aria-hidden="true">
            <div class="struk-head">STRUK TOPUP<?= icon('receipt') ?></div>
            <div class="struk-row"><span>Voucher game</span><b>27.000</b></div>
            <div class="struk-tear">
                <div class="struk-total"><span>Total</span><b>Rp 27.000</b></div>
                <span class="struk-paid">LUNAS</span>
            </div>
            <div class="barcode"></div>
        </div>
    </div>
    <form class="auth-card" method="post">
        <input type="hidden" name="_csrf" value="<?= csrf() ?>">
        <h2>Daftar TopUpKu</h2>
        <p>Sudah terdaftar? <a href="?page=login">Masuk</a></p>
        <label>Nama lengkap<input name="nama" required maxlength="100" autocomplete="name"></label>
        <label>Email<input type="email" name="email" required maxlength="100" autocomplete="email"></label>
        <label>Nomor HP<input type="tel" name="no_hp" required maxlength="15" autocomplete="tel"></label>
        <label>Kata sandi<input type="password" name="password" required minlength="8" autocomplete="new-password"><small>Minimal 8 karakter</small></label>
        <button class="btn btn-primary full">Buat akun</button>
    </form>
</section>
