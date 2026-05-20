<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = db();
$check_admin = $db->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
if ($check_admin && $check_admin->num_rows === 0) {
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (nama, role, password) VALUES ('admin', 'admin', ?)");
    $stmt->bind_param('s', $default_password);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    App::redirect('/login.php');
}

if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    App::setFlash('Invalid request', 'danger');
    App::redirect('/login.php');
}

$nama = trim($_POST['nama'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($nama) || empty($password)) {
    App::setFlash('Nama dan password harus diisi', 'danger');
    App::redirect('/login.php');
}

$stmt = $db->prepare("SELECT id, nama, password, role FROM users WHERE nama = ?");
$stmt->bind_param('s', $nama);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        session_regenerate_id(true);
        session_write_close();
        App::setFlash('Login berhasil', 'success');
        App::redirect('/');
    }
}

App::setFlash('Nama atau password salah', 'danger');
App::redirect('/login.php');
