<?php
require_once __DIR__ . '/core/Database.php';
session_start();
$db = db();

$result = $db->query("SELECT id, kode_aset FROM assets WHERE nama_barang = 'AIO Lenovo Hitam' ORDER BY id");
$total = $result->num_rows;

if ($total === 0) {
    echo "<p>Tidak ada AIO Lenovo Hitam ditemukan.</p>";
    exit;
}

$ids = [];
while ($row = $result->fetch_assoc()) {
    $ids[] = $row['id'];
}

$hapus = array_slice($ids, 12);
if (count($hapus) > 0) {
    $idStr = implode(',', $hapus);
    $db->query("DELETE FROM assets WHERE id IN ($idStr)");
    echo "<div style='background:#ffe6e6;padding:20px;border-left:4px solid red;font-family:sans-serif;'>";
    echo "<h2 style='color:red;'>✅ Berhasil hapus " . count($hapus) . " duplikat</h2>";
    echo "<p>Tersisa " . min($total, 12) . " AIO Lenovo Hitam (PC-0001 s/d PC-0012)</p>";
    echo "<p><a href='/assets/' style='display:inline-block;padding:10px 24px;background:#1a1a2e;color:#fff;text-decoration:none;border-radius:4px;'>Lihat Aset →</a></p>";
    echo "</div>";
} else {
    echo "<p>Hanya ada $total data, tidak ada duplikat.</p>";
}
