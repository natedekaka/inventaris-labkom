<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/functions.php';

$db = Database::getInstance()->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID aset tidak valid']);
    exit;
}

$stmt = $db->prepare("
    SELECT a.id, a.kode_aset, a.nama_barang, a.status, a.kondisi,
           c.nama_kategori as kategori
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if ($asset) {
    echo json_encode([
        'success' => true,
        'id' => $asset['id'],
        'kode_aset' => $asset['kode_aset'],
        'nama_barang' => $asset['nama_barang'],
        'status' => $asset['status'],
        'kondisi' => $asset['kondisi'],
        'kategori' => $asset['kategori']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Aset tidak ditemukan']);
}
