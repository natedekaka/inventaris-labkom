<?php
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/functions.php';

session_start();
App::requireLogin();

$db = db();

$kategori = $db->query("SELECT * FROM categories ORDER BY nama_kategori");
$lokasi = $db->query("SELECT * FROM locations ORDER BY nama_lokasi");

$kode = generateKodeAset('PRN');
$nama = 'Printer Epson L3251';
$category_id = 0;
$location_id = 0;
$merek = 'Epson';
$model = 'L3251';
$tanggal_beli = '2026-01-01';
$harga = 0;
$kondisi = 'baik';
$status = 'tersedia';

$cat = $db->query("SELECT id FROM categories WHERE nama_kategori LIKE '%printer%' LIMIT 1");
if ($cat && $cat->num_rows > 0) {
    $category_id = $cat->fetch_assoc()['id'];
}

$loc = $db->query("SELECT id FROM locations LIMIT 1");
if ($loc && $loc->num_rows > 0) {
    $location_id = $loc->fetch_assoc()['id'];
}

$stmt = $db->prepare("INSERT INTO assets (kode_aset, nama_barang, category_id, merek, model, harga, tanggal_beli, kondisi, status, location_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssisssdsss', $kode, $nama, $category_id, $merek, $model, $harga, $tanggal_beli, $kondisi, $status, $location_id);

if ($stmt->execute()) {
    echo "<h2 style='color:green;'>✅ Berhasil menambahkan:</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-family:sans-serif;'>";
    echo "<tr><td>Kode Aset</td><td><strong>$kode</strong></td></tr>";
    echo "<tr><td>Nama</td><td>$nama</td></tr>";
    echo "<tr><td>Merek</td><td>$merek</td></tr>";
    echo "<tr><td>Model</td><td>$model</td></tr>";
    echo "<tr><td>Tanggal Beli</td><td>$tanggal_beli</td></tr>";
    echo "<tr><td>Kategori ID</td><td>$category_id</td></tr>";
    echo "<tr><td>Lokasi ID</td><td>$location_id</td></tr>";
    echo "</table>";
    echo "<p><a href='/assets/' style='display:inline-block;margin-top:16px;padding:8px 20px;background:#1a1a2e;color:#fff;text-decoration:none;border-radius:4px;'>Lihat di Aset →</a></p>";
} else {
    echo "<h2 style='color:red;'>❌ Gagal: " . $stmt->error . "</h2>";
}
