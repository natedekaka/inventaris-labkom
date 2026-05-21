<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';

App::requireLogin();

$kode = trim($_GET['kode'] ?? '');
$excludeId = (int)($_GET['exclude_id'] ?? 0);

if (empty($kode)) {
    echo json_encode(['available' => false, 'message' => 'Kode tidak boleh kosong']);
    exit;
}

$db = Database::getInstance()->getConnection();

if ($excludeId > 0) {
    $stmt = $db->prepare("SELECT id FROM assets WHERE kode_aset = ? AND id != ?");
    $stmt->bind_param('si', $kode, $excludeId);
} else {
    $stmt = $db->prepare("SELECT id FROM assets WHERE kode_aset = ?");
    $stmt->bind_param('s', $kode);
}
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;

if ($exists) {
    echo json_encode(['available' => false, 'message' => 'Kode "' . $kode . '" sudah digunakan']);
} else {
    echo json_encode(['available' => true, 'message' => 'Kode tersedia']);
}
