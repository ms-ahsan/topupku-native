<?php
require_login();
$me = find_user($pdo, (int) user()['id_user']);
?>
<section class="page-head compact">
    <h1>Atur profil.</h1>
    <p>Kelola informasi kontak dan keamanan akun.</p>
</section>
<form class="panel profile-form" method="post">
    <input type="hidden" name="_csrf" value="<?= csrf() ?>">
    <label>Nama lengkap<input name="nama" required value="<?= e($me['nama']) ?>"></label>
    <label>Email<input value="<?= e($me['email']) ?>" disabled><small>Email tidak dapat diubah.</small></label>
    <label>Nomor HP<input name="no_hp" required value="<?= e($me['no_hp']) ?>"></label>
    <hr>
    <h3>Ganti kata sandi <small>(opsional)</small></h3>
    <label>Kata sandi baru<input type="password" name="password" minlength="8"></label>
    <label>Ulangi kata sandi<input type="password" name="password_confirm" minlength="8"></label>
    <button class="btn btn-primary">Simpan perubahan</button>
</form>
