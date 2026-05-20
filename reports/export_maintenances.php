<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

$db = Database::getInstance()->getConnection();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="maintenances_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID',
    'Aset',
    'Deskripsi',
    'Tanggal Mulai',
    'Tanggal Selesai',
    'Biaya',
    'Status'
]);

if ($dari && $sampai) {
    $stmt = $db->prepare("
        SELECT m.id, a.nama_barang, m.deskripsi, m.tanggal_maintenance as tanggal_mulai, m.status, m.biaya
        FROM maintenances m
        JOIN assets a ON m.asset_id = a.id
        WHERE m.tanggal_maintenance BETWEEN ? AND ?
        ORDER BY m.tanggal_maintenance DESC
    ");
    $stmt->bind_param('ss', $dari, $sampai);
} else {
    $stmt = $db->prepare("
        SELECT m.id, a.nama_barang, m.deskripsi, m.tanggal_maintenance as tanggal_mulai, m.status, m.biaya
        FROM maintenances m
        JOIN assets a ON m.asset_id = a.id
        ORDER BY m.tanggal_maintenance DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['nama_barang'],
        $row['deskripsi'],
        $row['tanggal_mulai'],
        '',
        $row['biaya'] ?? 0,
        $row['status']
    ]);
}

fclose($output);
exit;
