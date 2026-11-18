<?php
declare(strict_types=1);
/**
 * Create (or promote) an administrator account from the command line.
 *
 *   php scripts/create_admin.php "Alice Mwende" alice@example.com
 *
 * You are then asked for the password. No default admin password exists anywhere in
 * the code - that is deliberate (default credentials are a classic security hole).
 */
if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}
require __DIR__ . '/../includes/env.php';
load_env(dirname(__DIR__) . '/.env');
require __DIR__ . '/../config/database.php';

$name  = $argv[1] ?? '';
$email = strtolower($argv[2] ?? '');
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Usage: php scripts/create_admin.php \"Full Name\" email@example.com\n");
}

echo 'Choose a password (min 10 chars, letter + number): ';
$password = trim((string)fgets(STDIN));
if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    exit("Password too weak.\n");
}

$pdo = db();
$hash = password_hash($password, PASSWORD_DEFAULT);

$exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$exists->execute([$email]);
if ($exists->fetchColumn()) {
    $pdo->prepare("UPDATE users SET role = 'admin', password_hash = ? WHERE email = ?")->execute([$hash, $email]);
    echo "Existing user promoted to admin and password updated.\n";
} else {
    $adm = 'ADMIN-' . strtoupper(bin2hex(random_bytes(3)));
    $pdo->prepare("INSERT INTO users (full_name, email, admission_no, password_hash, role) VALUES (?, ?, ?, ?, 'admin')")
        ->execute([$name, $email, $adm, $hash]);
    echo "Admin created: $email\n";
}
