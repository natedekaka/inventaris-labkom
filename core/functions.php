<?php

function sanitize($input) {
    if ($input === null) return '';
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function generateKodeAset($prefix = 'INV') {
    $db = db();
    $likePattern = $prefix . '-%';
    $stmt = $db->prepare("SELECT kode_aset FROM assets WHERE kode_aset LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastCode = $row['kode_aset'];
        $number = (int) substr($lastCode, strlen($prefix) + 1);
        $number++;
    } else {
        $number = 1;
    }
    
    return $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
}

function formatRupiah($angka) {
    if ($angka === null || $angka === '') {
        $angka = 0;
    }
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') {
        return '-';
    }
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $date = date_create($tanggal);
    if (!$date) return '-';
    
    $day = date_format($date, 'j');
    $month = (int) date_format($date, 'n');
    $year = date_format($date, 'Y');
    
    return $day . ' ' . $bulan[$month] . ' ' . $year;
}

function generateQRCode($data, $size = 150) {
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
    return $url;
}

function logActivity($user_id, $user_name, $action, $table_name, $record_id = null, $description = '') {
    try {
        $db = db();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, user_name, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt->bind_param('isssiss', $user_id, $user_name, $action, $table_name, $record_id, $description, $ip);
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

function logAssetAction($asset_id, $user_id, $action, $details = null) {
    try {
        $db = db();
        $stmt = $db->prepare("INSERT INTO asset_logs (asset_id, user_id, action, details) VALUES (?, ?, ?, ?)");
        $details_json = $details ? json_encode($details) : null;
        $stmt->bind_param('iiss', $asset_id, $user_id, $action, $details_json);
        return $stmt->execute();
    } catch (Exception $e) {
        // Silent fail — asset_logs is non-critical, tabel mungkin belum di-migrate
        return false;
    }
}
