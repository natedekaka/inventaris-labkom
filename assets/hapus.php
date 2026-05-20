<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin']);

$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("DELETE FROM assets WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    App::setFlash('Aset berhasil dihapus', 'success');
} else {
    App::setFlash('Gagal menghapus aset', 'danger');
}

App::redirect('/assets/');
