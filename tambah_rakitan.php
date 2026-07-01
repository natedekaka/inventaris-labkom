<?php
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/functions.php';

session_start();
$db = db();

echo "<h1>Tambah PC Rakitan</h1>";

$cat_id = null;
$cat = $db->query("SELECT id FROM categories WHERE LOWER(nama_kategori) = 'pc desktop' LIMIT 1");
if ($cat && $cat->num_rows > 0) {
    $cat_id = $cat->fetch_assoc()['id'];
    echo "<p>✅ Kategori <strong>PC Desktop</strong> (ID: $cat_id)</p>";
} else {
    $db->query("INSERT INTO categories (nama_kategori) VALUES ('PC Desktop')");
    $cat_id = $db->insert_id;
}

$loc_id = null;
$loc = $db->query("SELECT id FROM locations ORDER BY id ASC LIMIT 1");
if ($loc && $loc->num_rows > 0) {
    $loc_id = $loc->fetch_assoc()['id'];
} else {
    $db->query("INSERT INTO locations (nama_lokasi) VALUES ('LAB 1')");
    $loc_id = $db->insert_id;
}

$last = $db->query("SELECT kode_aset FROM assets ORDER BY id DESC LIMIT 1");
$nextNum = 1;
if ($last && $last->num_rows > 0) {
    $lastKode = $last->fetch_assoc()['kode_aset'];
    if (preg_match('/(\d+)$/', $lastKode, $m)) {
        $nextNum = (int)$m[1] + 1;
    }
}

$stmt = $db->prepare("INSERT INTO assets (kode_aset, nama_barang, category_id, merek, model, harga, tanggal_beli, kondisi, status, location_id) VALUES (?, ?, ?, ?, ?, 0, '2026-01-01', ?, 'tersedia', ?)");

$data = [];
for ($i = 0; $i < 15; $i++) {
    $data[] = 'baik';
}
for ($i = 0; $i < 5; $i++) {
    $data[] = 'rusak_berat';
}

$inserted = 0;
foreach ($data as $idx => $kondisi) {
    $kode = sprintf('PC-%04d', $nextNum + $idx);
    $nama = 'PC Rakitan';
    $merek = 'Rakitan';
    $model = 'Proc Dual Core';
    $stmt->bind_param('ssssssi', $kode, $nama, $cat_id, $merek, $model, $kondisi, $loc_id);
    if ($stmt->execute()) {
        $inserted++;
    } else {
        echo "<p style='color:red;'>❌ Gagal $kode: " . $stmt->error . "</p>";
    }
}

echo "<div style='background:#e6ffe6;padding:20px;border-left:4px solid green;margin:16px 0;font-family:sans-serif;'>";
echo "<h2 style='color:green;margin:0 0 12px 0;'>✅ BERHASIL! $inserted aset ditambahkan</h2>";
echo "<table border='1' cellpadding='6' style='border-collapse:collapse;'>";
echo "<tr><th>No</th><th>Kode</th><th>Nama</th><th>Kondisi</th></tr>";
foreach ($data as $idx => $kondisi) {
    $k = sprintf('PC-%04d', $nextNum + $idx);
    $label = $kondisi === 'baik' ? 'Baik' : 'Rusak Berat';
    echo "<tr><td>" . ($idx+1) . "</td><td>$k</td><td>PC Rakitan</td><td>$label</td></tr>";
}
echo "</table>";
echo "<p style='margin-top:16px;'><a href='/assets/' style='display:inline-block;padding:10px 24px;background:#1a1a2e;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold;'>Lihat di Aset →</a></p>";
echo "</div>";
