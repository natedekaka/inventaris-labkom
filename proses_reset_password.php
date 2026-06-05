<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    App::redirect('/login.php');
}

if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    App::setFlash('Invalid request', 'danger');
    App::redirect('/login.php');
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if (empty($token) || empty($password) || empty($password_confirm)) {
    App::setFlash('Semua field harus diisi', 'danger');
    App::redirect('/reset_password.php?token=' . urlencode($token));
}

if (strlen($password) < 6) {
    App::setFlash('Password minimal 6 karakter', 'danger');
    App::redirect('/reset_password.php?token=' . urlencode($token));
}

if ($password !== $password_confirm) {
    App::setFlash('Konfirmasi password tidak cocok', 'danger');
    App::redirect('/reset_password.php?token=' . urlencode($token));
}

$db = db();

$stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    App::setFlash('Token tidak valid atau sudah kedaluwarsa', 'danger');
    App::redirect('/forgot_password.php');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
$stmt->bind_param('si', $hashedPassword, $user['id']);

if ($stmt->execute()) {
    App::setFlash('Password berhasil direset. Silakan login dengan password baru.', 'success');
    App::redirect('/login.php');
} else {
    App::setFlash('Gagal mereset password', 'danger');
    App::redirect('/reset_password.php?token=' . urlencode($token));
}
