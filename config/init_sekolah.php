<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../core/functions.php';

// Session timeout: 30 menit idle
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 0);
if (session_status() === PHP_SESSION_ACTIVE) {
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header('Location: /login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

$nama_sekolah = getenv('SCHOOL_NAME') ?: 'SMA Negeri 6 Cimahi';
$alamat_sekolah = getenv('SCHOOL_ADDRESS') ?: 'Jl. Pendidikan No. 1';
$kota_sekolah = getenv('SCHOOL_CITY') ?: 'Cimahi';

define('NAMA_SEKOLAH', $nama_sekolah);
define('ALAMAT_SEKOLAH', $alamat_sekolah);
define('KOTA_SEKOLAH', $kota_sekolah);
