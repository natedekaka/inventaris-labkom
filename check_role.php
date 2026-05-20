<?php
session_start();
require_once __DIR__ . '/core/Database.php';
$db = Database::getInstance()->getConnection();
$result = $db->query("SELECT id, nama, role FROM users ORDER BY id");
echo "<h3>Daftar User & Role</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
echo "<tr><th>ID</th><th>Nama</th><th>Role</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['nama']}</td><td><strong>{$row['role']}</strong></td></tr>";
}
echo "</table>";
echo "<p><br>Session saat ini:<br>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'TIDAK ADA') . "<br>";
echo "nama: " . ($_SESSION['nama'] ?? 'TIDAK ADA') . "<br>";
echo "role: <strong>" . ($_SESSION['role'] ?? 'TIDAK ADA') . "</strong></p>";
