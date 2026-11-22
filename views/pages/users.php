<?php
require_admin();
$rows = customers_with_stats($pdo);
?>
<section class="admin-head">
    <div>
        <h1>Kelola pengguna.</h1>
        <p><?= count($rows) ?> akun pelanggan terdaftar.</p>
    </div>
</section>
<div class="panel table-scroll">
    <table>
        <thead><tr><th>NAMA</th><th>EMAIL</th><th>NO. HP</th><th>TRANSAKSI</th><th>STATUS</th><th>AKSI</th></tr></thead>
        <tbody>
            <?php foreach ($rows as $u): ?>
                <tr>
                    <td><b><?= e($u['nama']) ?></b></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['no_hp']) ?></td>
                    <td><?= (int) $u['transaksi'] ?></td>
                    <td><?= e($u['status']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                            <button class="link-button"><?= $u['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
