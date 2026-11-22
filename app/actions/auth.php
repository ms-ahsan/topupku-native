<?php
declare(strict_types=1);

function handle_auth_action(PDO $pdo, string $page): bool
{
    if ($page === 'register') {
        $name = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = preg_replace('/\D/', '', $_POST['no_hp'] ?? '');
        $password = $_POST['password'] ?? '';
        if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($phone) < 9 || strlen($phone) > 15 || strlen($password) < 8) {
            throw new RuntimeException('Periksa kembali data. Kata sandi minimal 8 karakter.');
        }
        $query = $pdo->prepare('INSERT INTO users(nama,email,no_hp,password) VALUES(?,?,?,?)');
        $query->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
        flash('Akun berhasil dibuat. Silakan masuk.');
        go('?page=login');
    }

    if ($page === 'login') {
        $query = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif'");
        $query->execute([strtolower(trim($_POST['email'] ?? ''))]);
        $account = $query->fetch();
        if (!$account || !password_verify($_POST['password'] ?? '', $account['password'])) {
            throw new RuntimeException('Email atau kata sandi tidak cocok.');
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id_user' => $account['id_user'], 'nama' => $account['nama'],
            'email' => $account['email'], 'role' => $account['role'],
        ];
        go($account['role'] === 'admin' ? '?page=admin' : '?page=home');
    }

    if ($page === 'logout') {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        go('?');
    }

    return false;
}
