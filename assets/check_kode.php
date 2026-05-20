<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/functions.php';

$db = Database::getInstance()->getConnection();

$kode = isset($_GET['kode']) ? $_GET['kode'] : '';

if (empty($kode)) {
    echo json_encode(['success' => false, 'message' => 'Kode aset tidak boleh kosong']);
    exit;
}

$stmt = $db->prepare("SELECT id, kode_aset, nama_barang FROM assets WHERE kode_aset = ? LIMIT 1");
$stmt->bind_param('s', $kode);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if ($asset) {
    echo json_encode([
        'success' => true,
        'id' => $asset['id'],
        'kode_aset' => $asset['kode_aset'],
        'nama_barang' => $asset['nama_barang']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Kode aset tidak ditemukan: ' . sanitize($kode)]);
}
