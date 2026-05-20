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
header('Content-Disposition: attachment; filename="borrowings_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID',
    'Aset',
    'Peminjam',
    'Tanggal Pinjam',
    'Rencana Kembali',
    'Tanggal Kembali',
    'Status',
    'Keperluan'
]);

if ($dari && $sampai) {
    $stmt = $db->prepare("
        SELECT b.id, a.nama_barang, u.nama as nama_peminjam, b.tanggal_pinjam, b.rencana_kembali, b.tanggal_kembali, b.status, b.keperluan
        FROM borrowings b
        JOIN assets a ON b.asset_id = a.id
        JOIN users u ON b.user_id = u.id
        WHERE b.tanggal_pinjam BETWEEN ? AND ?
        ORDER BY b.tanggal_pinjam DESC
    ");
    $stmt->bind_param('ss', $dari, $sampai);
} else {
    $stmt = $db->prepare("
        SELECT b.id, a.nama_barang, u.nama as nama_peminjam, b.tanggal_pinjam, b.rencana_kembali, b.tanggal_kembali, b.status, b.keperluan
        FROM borrowings b
        JOIN assets a ON b.asset_id = a.id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.tanggal_pinjam DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['nama_barang'],
        $row['nama_peminjam'],
        $row['tanggal_pinjam'],
        $row['rencana_kembali'],
        $row['tanggal_kembali'] ?? '',
        $row['status'],
        $row['keperluan']
    ]);
}

fclose($output);
exit;
