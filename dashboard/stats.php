<?php

function getTotalAssets() {
    $conn = Database::getInstance()->getConnection();
    $result = $conn->query("SELECT COUNT(*) as total FROM assets");
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getAssetsByConditionCount($kondisi) {
    $conn = Database::getInstance()->getConnection();
    if ($kondisi === 'rusak') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE kondisi IN ('rusak_ringan', 'rusak_berat')");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc()['total'] : 0;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE kondisi = ?");
    $stmt->bind_param('s', $kondisi);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getBorrowedAssets() {
    $conn = Database::getInstance()->getConnection();
    $result = $conn->query("SELECT COUNT(*) as total FROM assets WHERE status = 'dipinjam'");
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getRecentAssets($limit = 5) {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("SELECT a.*, c.nama_kategori, l.nama_lokasi FROM assets a LEFT JOIN categories c ON a.category_id = c.id LEFT JOIN locations l ON a.location_id = l.id ORDER BY a.created_at DESC LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $assets = [];
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }
    return $assets;
}

function getOverdueBorrowings() {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("
        SELECT b.*, a.kode_aset, a.nama_barang as nama_aset, u.nama as nama_peminjam,
               DATEDIFF(CURDATE(), b.rencana_kembali) as hari_terlambat
        FROM borrowings b
        JOIN assets a ON b.asset_id = a.id
        JOIN users u ON b.user_id = u.id
        WHERE b.status = 'dipinjam' AND b.rencana_kembali < CURDATE()
        ORDER BY b.rencana_kembali ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $overdue = [];
    while ($row = $result->fetch_assoc()) {
        $overdue[] = $row;
    }
    return $overdue;
}

function getAssetsByCategory() {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("
        SELECT c.nama_kategori, COUNT(*) as jumlah 
        FROM assets a 
        LEFT JOIN categories c ON a.category_id = c.id 
        GROUP BY c.nama_kategori 
        ORDER BY jumlah DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = [];
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['nama_kategori'] ?? 'Tanpa Kategori';
        $data[] = $row['jumlah'];
    }
    return ['labels' => $labels, 'data' => $data];
}

function getAssetsByConditionChart() {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("
        SELECT kondisi, COUNT(*) as jumlah 
        FROM assets 
        GROUP BY kondisi
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $condition_map = [
        'baik' => ['label' => 'Baik', 'color' => 'rgba(34, 197, 94, 0.8)'],
        'rusak_ringan' => ['label' => 'Rusak Ringan', 'color' => 'rgba(234, 179, 8, 0.8)'],
        'rusak_berat' => ['label' => 'Rusak Berat', 'color' => 'rgba(239, 68, 68, 0.8)']
    ];
    $labels = [];
    $data = [];
    $colors = [];
    while ($row = $result->fetch_assoc()) {
        $kondisi = $row['kondisi'];
        $labels[] = $condition_map[$kondisi]['label'] ?? $kondisi;
        $data[] = $row['jumlah'];
        $colors[] = $condition_map[$kondisi]['color'] ?? 'rgba(156, 163, 175, 0.8)';
    }
    return ['labels' => $labels, 'data' => $data, 'colors' => $colors];
}

function getMonthlyBorrowings($months = 6) {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(tanggal_pinjam, '%Y-%m') as bulan, 
               DATE_FORMAT(tanggal_pinjam, '%b %Y') as label_bulan,
               COUNT(*) as jumlah
        FROM borrowings
        WHERE tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(tanggal_pinjam, '%Y-%m')
        ORDER BY bulan ASC
    ");
    $stmt->bind_param('i', $months);
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = [];
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['label_bulan'];
        $data[] = $row['jumlah'];
    }
    return ['labels' => $labels, 'data' => $data];
}

function getUserBorrowingsCount($userId) {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM borrowings WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getUserOverdueCount($userId) {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM borrowings WHERE user_id = ? AND status = 'dipinjam' AND rencana_kembali < CURDATE()");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getUserRecentBorrowings($userId, $limit = 5) {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("
        SELECT b.*, a.kode_aset, a.nama_barang as nama_aset
        FROM borrowings b
        JOIN assets a ON b.asset_id = a.id
        WHERE b.user_id = ?
        ORDER BY b.tanggal_pinjam DESC
        LIMIT ?
    ");
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $borrowings = [];
    while ($row = $result->fetch_assoc()) {
        $borrowings[] = $row;
    }
    return $borrowings;
}
