<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$nama = trim($_POST['nama'] ?? '');

if (empty($nama)) {
    echo json_encode(['success' => false, 'message' => 'Nama lokasi harus diisi']);
    exit;
}

if (strlen($nama) > 100) {
    echo json_encode(['success' => false, 'message' => 'Nama lokasi maksimal 100 karakter']);
    exit;
}

$db = db();

// Cek duplikat
$check = $db->prepare("SELECT id FROM locations WHERE nama_lokasi = ?");
$check->bind_param('s', $nama);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Lokasi "' . $nama . '" sudah ada']);
    exit;
}

$stmt = $db->prepare("INSERT INTO locations (nama_lokasi) VALUES (?)");
$stmt->bind_param('s', $nama);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'id' => $stmt->insert_id,
        'nama' => $nama
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan lokasi']);
}
