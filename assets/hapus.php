<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    App::redirect('/assets/');
}

if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    App::setFlash('Invalid request', 'danger');
    App::redirect('/assets/');
}

$db = db();
$id = (int)($_POST['delete_id'] ?? 0);

$stmt = $db->prepare("DELETE FROM assets WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    App::setFlash('Aset berhasil dihapus', 'success');
} else {
    App::setFlash('Gagal menghapus aset', 'danger');
}

App::redirect('/assets/');
