<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/functions.php';

App::requireLogin();

$db = Database::getInstance()->getConnection();

$fullAccessRoles = ['admin', 'lab_assistant', 'operator'];
$isFullAccess = in_array($_SESSION['role'] ?? '', $fullAccessRoles);

$sql = "SELECT b.id, u.nama AS nama_peminjam, a.nama_barang AS nama_aset, a.kode_aset, b.rencana_kembali,
               DATEDIFF(CURDATE(), b.rencana_kembali) AS hari_terlambat
        FROM borrowings b
        JOIN assets a ON b.asset_id = a.id
        JOIN users u ON b.user_id = u.id
        WHERE b.status = 'dipinjam' AND b.rencana_kembali < CURDATE()";

if (!$isFullAccess) {
    $sql .= " AND b.user_id = ?";
}

$sql .= " ORDER BY hari_terlambat DESC LIMIT 20";

$stmt = $db->prepare($sql);

if (!$isFullAccess) {
    $stmt->bind_param('i', $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $hari = (int)$row['hari_terlambat'];

    if ($hari >= 7) {
        $severity = 'critical';
    } elseif ($hari >= 3) {
        $severity = 'warning';
    } else {
        $severity = 'info';
    }

    $notifications[] = [
        'id' => (int)$row['id'],
        'nama_peminjam' => $row['nama_peminjam'],
        'nama_aset' => $row['nama_aset'],
        'kode_aset' => $row['kode_aset'],
        'rencana_kembali' => $row['rencana_kembali'],
        'hari_terlambat' => $hari,
        'severity' => $severity,
    ];
}

echo json_encode([
    'count' => count($notifications),
    'notifications' => $notifications,
]);
