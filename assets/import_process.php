<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin']);

$db = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    App::setFlash('Akses tidak valid', 'danger');
    App::redirect('/assets/import.php');
}

if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    App::setFlash('Invalid request', 'danger');
    App::redirect('/assets/import.php');
}

$csvContent = $_POST['csv_data'] ?? '';
if (empty(trim($csvContent))) {
    App::setFlash('Data CSV kosong', 'danger');
    App::redirect('/assets/import.php');
}

$lines = explode("\n", trim($csvContent));
$header = str_getcsv(array_shift($lines));
$header = array_map('trim', $header);

$imported = 0;
$errors = [];

foreach ($lines as $lineIndex => $line) {
    $line = trim($line);
    if (empty($line)) continue;

    $row = str_getcsv($line);
    $row = array_map('trim', $row);
    $data = array_combine($header, $row);

    $rowNum = $lineIndex + 2;

    if (empty($data['kode_aset'])) {
        $errors[] = "Baris $rowNum: kode_aset wajib diisi";
        continue;
    }
    if (empty($data['nama_barang'])) {
        $errors[] = "Baris $rowNum: nama_barang wajib diisi";
        continue;
    }

    $checkStmt = $db->prepare("SELECT id FROM assets WHERE kode_aset = ?");
    $checkStmt->bind_param('s', $data['kode_aset']);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $errors[] = "Baris $rowNum: kode_aset '{$data['kode_aset']}' sudah ada";
        continue;
    }

    $catId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
    if ($catId) {
        $catCheck = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $catCheck->bind_param('i', $catId);
        $catCheck->execute();
        if ($catCheck->get_result()->num_rows === 0) {
            $errors[] = "Baris $rowNum: category_id $catId tidak ditemukan";
            continue;
        }
    }

    $locId = !empty($data['location_id']) ? (int)$data['location_id'] : null;
    if ($locId) {
        $locCheck = $db->prepare("SELECT id FROM locations WHERE id = ?");
        $locCheck->bind_param('i', $locId);
        $locCheck->execute();
        if ($locCheck->get_result()->num_rows === 0) {
            $errors[] = "Baris $rowNum: location_id $locId tidak ditemukan";
            continue;
        }
    }

    $kondisi = in_array($data['kondisi'] ?? '', ['baik', 'rusak_ringan', 'rusak_berat']) ? $data['kondisi'] : 'baik';
    $status = 'tersedia';
    $harga = (float)($data['harga'] ?? 0);
    if ($harga < 0) $harga = 0;
    $spesifikasi = $data['keterangan'] ?? '';

    $stmt = $db->prepare("INSERT INTO assets (kode_aset, nama_barang, category_id, location_id, kondisi, status, harga, spesifikasi, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssssssds', $data['kode_aset'], $data['nama_barang'], $catId, $locId, $kondisi, $status, $harga, $spesifikasi);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        logAssetAction($newId, $_SESSION['user_id'], 'created', ['source' => 'csv_import']);
        $imported++;
    } else {
        $errors[] = "Baris $rowNum: Gagal insert - " . $db->error;
    }
}

if (!empty($errors)) {
    $_SESSION['import_errors'] = $errors;
}

if ($imported > 0 && empty($errors)) {
    App::setFlash("Berhasil mengimport $imported aset", 'success');
} elseif ($imported > 0 && !empty($errors)) {
    App::setFlash("Berhasil mengimport $imported aset dengan " . count($errors) . " error", 'warning');
} else {
    App::setFlash("Gagal mengimport. " . count($errors) . " error ditemukan.", 'danger');
}

App::redirect('/assets/import.php');
