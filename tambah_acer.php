<?php
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/functions.php';

session_start();
$db = db();

echo "<h1>Tambah 21 AIO ACER</h1>";

$cat_id = null;
$cat = $db->query("SELECT id FROM categories WHERE LOWER(nama_kategori) = 'pc desktop' LIMIT 1");
if ($cat && $cat->num_rows > 0) {
    $cat_id = $cat->fetch_assoc()['id'];
    echo "<p>✅ Kategori <strong>PC Desktop</strong> (ID: $cat_id)</p>";
} else {
    $db->query("INSERT INTO categories (nama_kategori) VALUES ('PC Desktop')");
    $cat_id = $db->insert_id;
    echo "<p>✅ Kategori <strong>PC Desktop</strong> dibuat (ID: $cat_id)</p>";
}

$loc_id = null;
$loc = $db->query("SELECT id FROM locations ORDER BY id ASC LIMIT 1");
if ($loc && $loc->num_rows > 0) {
    $loc_id = $loc->fetch_assoc()['id'];
    echo "<p>✅ Menggunakan Lokasi ID: $loc_id</p>";
} else {
    $db->query("INSERT INTO locations (nama_lokasi) VALUES ('LAB 1')");
    $loc_id = $db->insert_id;
    echo "<p>✅ Lokasi <strong>LAB 1</strong> dibuat (ID: $loc_id)</p>";
}

$last = $db->query("SELECT kode_aset FROM assets ORDER BY id DESC LIMIT 1");
$nextNum = 1;
if ($last && $last->num_rows > 0) {
    $lastKode = $last->fetch_assoc()['kode_aset'];
    if (preg_match('/(\d+)$/', $lastKode, $m)) {
        $nextNum = (int)$m[1] + 1;
    }
}

$inserted = 0;
$stmt = $db->prepare("INSERT INTO assets (kode_aset, nama_barang, category_id, merek, model, harga, tanggal_beli, kondisi, status, location_id) VALUES (?, ?, ?, ?, ?, 0, '2026-01-01', 'baik', 'tersedia', ?)");

for ($i = 0; $i < 21; $i++) {
    $kode = sprintf('PC-%04d', $nextNum + $i);
    $nama = 'AIO ACER';
    $merek = 'ACER';
    $model = 'Z460G-C';
    $stmt->bind_param('sssssi', $kode, $nama, $cat_id, $merek, $model, $loc_id);
    if ($stmt->execute()) {
        $inserted++;
    } else {
        echo "<p style='color:red;'>❌ Gagal $kode: " . $stmt->error . "</p>";
    }
}

echo "<div style='background:#e6ffe6;padding:20px;border-left:4px solid green;margin:16px 0;font-family:sans-serif;'>";
echo "<h2 style='color:green;margin:0 0 12px 0;'>✅ BERHASIL! $inserted dari 21 aset ditambahkan</h2>";
echo "<table border='1' cellpadding='6' style='border-collapse:collapse;'>";
echo "<tr><th>No</th><th>Kode</th><th>Nama</th><th>Merek</th><th>Model</th></tr>";
for ($i = 0; $i < $inserted; $i++) {
    $k = sprintf('PC-%04d', $nextNum + $i);
    echo "<tr><td>" . ($i+1) . "</td><td>$k</td><td>AIO ACER</td><td>ACER</td><td>Z460G-C</td></tr>";
}
echo "</table>";
echo "<p style='margin-top:16px;'><a href='/assets/' style='display:inline-block;padding:10px 24px;background:#1a1a2e;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold;'>Lihat di Aset →</a></p>";
echo "</div>";
