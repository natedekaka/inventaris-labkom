<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/functions.php';

App::requireLogin();

$db = Database::getInstance()->getConnection();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode(['assets' => [], 'users' => []]);
    exit;
}

$search = '%' . $q . '%';

$assets = [];
$stmt = $db->prepare("SELECT id, kode_aset, nama_barang FROM assets WHERE kode_aset LIKE ? OR nama_barang LIKE ? LIMIT 10");
$stmt->bind_param('ss', $search, $search);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

$users = [];
$stmt = $db->prepare("SELECT id, nama FROM users WHERE nama LIKE ? LIMIT 10");
$stmt->bind_param('s', $search);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(['assets' => $assets, 'users' => $users]);
