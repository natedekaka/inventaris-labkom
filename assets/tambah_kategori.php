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
    echo json_encode(['success' => false, 'message' => 'Nama kategori harus diisi']);
    exit;
}

if (strlen($nama) > 50) {
    echo json_encode(['success' => false, 'message' => 'Nama kategori maksimal 50 karakter']);
    exit;
}

$db = db();

// Cek duplikat
$check = $db->prepare("SELECT id FROM categories WHERE nama_kategori = ?");
$check->bind_param('s', $nama);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Kategori "' . $nama . '" sudah ada']);
    exit;
}

$stmt = $db->prepare("INSERT INTO categories (nama_kategori) VALUES (?)");
$stmt->bind_param('s', $nama);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'id' => $stmt->insert_id,
        'nama' => $nama
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan kategori']);
}
