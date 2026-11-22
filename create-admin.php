<?php
require __DIR__.'/config.php';
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run this script from CLI.'); }
$email=$argv[1]??'admin@topupku.id'; $password=$argv[2]??'';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8){fwrite(STDERR,"Usage: php create-admin.php admin@topupku.id password-min-8\n");exit(1);}
$q=$pdo->prepare('SELECT id_user FROM users WHERE email=?'); $q->execute([$email]);
if($q->fetchColumn()){fwrite(STDERR,"Email already exists.\n");exit(1);}
$pdo->prepare("INSERT INTO users(nama,email,no_hp,password,role,status) VALUES('Administrator',?,?,?,'admin','aktif')")->execute([$email,'080000000000',password_hash($password,PASSWORD_DEFAULT)]);
echo "Admin created: $email\n";
