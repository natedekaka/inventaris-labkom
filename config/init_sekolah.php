<?php

require_once __DIR__ . '/database.php';

$nama_sekolah = getenv('SCHOOL_NAME') ?: 'SMA Negeri 6 Cimahi';
$alamat_sekolah = getenv('SCHOOL_ADDRESS') ?: 'Jl. Pendidikan No. 1';
$kota_sekolah = getenv('SCHOOL_CITY') ?: 'Cimahi';

define('NAMA_SEKOLAH', $nama_sekolah);
define('ALAMAT_SEKOLAH', $alamat_sekolah);
define('KOTA_SEKOLAH', $kota_sekolah);
