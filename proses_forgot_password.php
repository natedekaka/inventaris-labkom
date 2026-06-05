<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    App::redirect('/forgot_password.php');
}

if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    App::setFlash('Invalid request', 'danger');
    App::redirect('/forgot_password.php');
}

$nama = trim($_POST['nama'] ?? '');

if (empty($nama)) {
    App::setFlash('Nama pengguna harus diisi', 'danger');
    App::redirect('/forgot_password.php');
}

$db = db();

$stmt = $db->prepare("SELECT id, nama FROM users WHERE nama = ?");
$stmt->bind_param('s', $nama);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    App::setFlash('Jika nama pengguna terdaftar, link reset akan dibuat', 'info');
    App::redirect('/forgot_password.php');
}

    $columnCheck = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
if ($columnCheck->num_rows === 0) {
    $migration = file_get_contents(__DIR__ . '/db/migration_reset_password.sql');
    if ($migration) {
        $db->query($migration);
    } else {
        $db->query("ALTER TABLE users 
            ADD COLUMN reset_token VARCHAR(64) NULL AFTER password,
            ADD COLUMN reset_expires DATETIME NULL AFTER reset_token,
            ADD INDEX idx_reset_token (reset_token)");
    }
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$cleanup = $db->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = ? AND reset_expires < NOW()");
$cleanup->bind_param('i', $user['id']);
$cleanup->execute();

$stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
$stmt->bind_param('ssi', $token, $expires, $user['id']);

if ($stmt->execute()) {
    $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
    $resetLink = rtrim($baseUrl, '/') . '/reset_password.php?token=' . urlencode($token);
    
    $_SESSION['reset_link'] = $resetLink;
    
    App::setFlash('Link reset password berhasil dibuat', 'success');
    App::redirect('/forgot_password.php');
} else {
    App::setFlash('Gagal membuat link reset', 'danger');
    App::redirect('/forgot_password.php');
}
