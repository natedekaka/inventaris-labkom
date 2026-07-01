<?php
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/functions.php';

session_start();
$db = db();

echo "<h1>Tambah Printer Epson L3251</h1>";

$printer_cat_id = null;
$cat = $db->query("SELECT id FROM categories WHERE LOWER(nama_kategori) = 'printer' LIMIT 1");
if ($cat && $cat->num_rows > 0) {
    $printer_cat_id = $cat->fetch_assoc()['id'];
    echo "<p>✅ Kategori <strong>Printer</strong> sudah ada (ID: $printer_cat_id)</p>";
} else {
    $db->query("INSERT INTO categories (nama_kategori) VALUES ('Printer')");
    $printer_cat_id = $db->insert_id;
    echo "<p>✅ Kategori <strong>Printer</strong> berhasil dibuat (ID: $printer_cat_id)</p>";
}

$lokasi_id = null;
$loc = $db->query("SELECT id FROM locations ORDER BY id ASC LIMIT 1");
if ($loc && $loc->num_rows > 0) {
    $lokasi_id = $loc->fetch_assoc()['id'];
    echo "<p>✅ Lokasi ID: $lokasi_id</p>";
} else {
    $db->query("INSERT INTO locations (nama_lokasi) VALUES ('Lab Komputer')");
    $lokasi_id = $db->insert_id;
    echo "<p>✅ Lokasi <strong>Lab Komputer</strong> berhasil dibuat (ID: $lokasi_id)</p>";
}

$kode = generateKodeAset('PRN');
$nama = 'Printer Epson L3251';
$merek = 'Epson';
$model = 'L3251';
$tgl_beli = '2026-01-01';
$harga = 0;
$kondisi = 'baik';
$status = 'tersedia';

$stmt = $db->prepare("INSERT INTO assets (kode_aset, nama_barang, category_id, merek, model, harga, tanggal_beli, kondisi, status, location_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssisssdsss', $kode, $nama, $printer_cat_id, $merek, $model, $harga, $tgl_beli, $kondisi, $status, $lokasi_id);

if ($stmt->execute()) {
    echo "<div style='background:#e6ffe6;padding:20px;border-left:4px solid green;margin:16px 0;font-family:sans-serif;'>";
    echo "<h2 style='color:green;margin:0 0 12px 0;'>✅ BERHASIL!</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "<tr><td>Kode Aset</td><td><strong>$kode</strong></td></tr>";
    echo "<tr><td>Nama</td><td>$nama</td></tr>";
    echo "<tr><td>Merek</td><td>$merek</td></tr>";
    echo "<tr><td>Model</td><td>$model</td></tr>";
    echo "<tr><td>Tanggal Beli</td><td>$tgl_beli</td></tr>";
    echo "<tr><td>Kategori</td><td>Printer</td></tr>";
    echo "</table>";
    echo "<p style='margin-top:16px;'><a href='/assets/?search=epson' style='display:inline-block;padding:10px 24px;background:#1a1a2e;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold;'>Lihat di Aset →</a></p>";
    echo "</div>";
} else {
    echo "<div style='background:#ffe6e6;padding:20px;border-left:4px solid red;margin:16px 0;font-family:sans-serif;'>";
    echo "<h2 style='color:red;margin:0 0 8px 0;'>❌ GAGAL</h2>";
    echo "<p>" . $stmt->error . "</p>";
    echo "</div>";
}
