<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/functions.php';

App::requireLogin();

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM borrowings WHERE status = 'dipinjam' AND rencana_kembali < CURDATE()");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['count' => (int)$row['count']]);
