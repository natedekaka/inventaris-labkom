<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/functions.php';

$db = Database::getInstance()->getConnection();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="assets_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Kode Aset',
    'Nama Barang',
    'Kategori',
    'Merek',
    'Serial Number',
    'Spesifikasi',
    'Harga',
    'Tanggal Beli',
    'Kondisi',
    'Status',
    'Lokasi'
]);

$stmt = $db->prepare("
    SELECT a.kode_aset, a.nama_barang, c.nama_kategori, a.merek, a.serial_number, a.spesifikasi,
           a.harga, a.tanggal_beli, a.kondisi, a.status, l.nama_lokasi
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN locations l ON a.location_id = l.id
    ORDER BY a.kode_aset
");

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['kode_aset'],
        $row['nama_barang'],
        $row['nama_kategori'] ?? '-',
        $row['merek'],
        $row['serial_number'] ?? '-',
        $row['spesifikasi'] ?? '-',
        $row['harga'],
        $row['tanggal_beli'],
        $row['kondisi'],
        $row['status'],
        $row['nama_lokasi'] ?? '-'
    ]);
}

fclose($output);
exit;
