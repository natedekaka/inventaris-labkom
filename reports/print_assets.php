<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/functions.php';

$db = db();

$stmt = $db->prepare("SELECT a.*, c.nama_kategori, l.nama_lokasi FROM assets a LEFT JOIN categories c ON a.category_id = c.id LEFT JOIN locations l ON a.location_id = l.id ORDER BY a.kode_aset");
$stmt->execute();
$result = $stmt->get_result();
$assets = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Aset - <?= sanitize(NAMA_SEKOLAH) ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 18mm;
        }
        @page :first {
            margin-top: 20mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }
        .no-print { display: none !important; }
        .kop-surat {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        .kop-surat h1 { font-size: 14pt; font-weight: bold; margin: 0 0 2px 0; }
        .kop-surat h2 { font-size: 11pt; font-weight: normal; margin: 0 0 2px 0; }
        .kop-surat p { font-size: 9pt; margin: 1px 0; color: #333; }
        .judul-laporan {
            text-align: center;
            margin: 15px 0;
        }
        .judul-laporan h3 { font-size: 12pt; font-weight: bold; text-decoration: underline; }
        .info-cetak {
            text-align: right;
            font-size: 9pt;
            margin-bottom: 10px;
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        thead { display: table-header-group; }
        th {
            border: 1px solid #000;
            padding: 5px 4px;
            font-weight: bold;
            text-align: center;
            background-color: #e0e0e0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            font-size: 8.5pt;
        }
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }
        tr { page-break-inside: avoid; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .ttd-wrapper {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .ttd-box {
            text-align: center;
            width: 45%;
        }
        .ttd-box p { margin: 2px 0; font-size: 9pt; }
        .ttd-box .jabatan { margin-bottom: 60px; }
        .ttd-box .nama { font-weight: bold; text-decoration: underline; }
        .footer-page {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-top: 10px;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        @media print {
            body { margin: 0; padding: 0; }
        }
        @media screen {
            body { background: #f5f5f5; padding: 20px; }
            .print-wrapper {
                max-width: 297mm;
                margin: 0 auto;
                background: #fff;
                padding: 18mm;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    <div class="print-wrapper">
        <!-- Kop Surat -->
        <div class="kop-surat">
            <h1><?= sanitize(NAMA_SEKOLAH) ?></h1>
            <p><?= sanitize(ALAMAT_SEKOLAH) ?>, <?= sanitize(KOTA_SEKOLAH) ?></p>
        </div>

        <!-- Tombol Cetak (hanya di layar) -->
        <div class="no-print" style="text-align:right;margin-bottom:10px;">
            <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:8px 20px;border-radius:6px;font-size:11pt;cursor:pointer;">
                🖨 Cetak / Print
            </button>
            <button onclick="window.close()" style="background:#6b7280;color:#fff;border:none;padding:8px 20px;border-radius:6px;font-size:11pt;cursor:pointer;margin-left:6px;">
                ✕ Tutup
            </button>
        </div>

        <!-- Judul Laporan -->
        <div class="judul-laporan">
            <h3>LAPORAN DATA ASET</h3>
        </div>

        <div class="info-cetak">
            Dicetak pada: <?= formatTanggal(date('Y-m-d')) ?>
        </div>

        <?php if (empty($assets)): ?>
            <p style="text-align:center;padding:40px 0;color:#666;font-size:11pt;">Tidak ada data aset</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:4%;">No</th>
                        <th style="width:10%;">Kode Aset</th>
                        <th style="width:16%;">Nama Barang</th>
                        <th style="width:10%;">Kategori</th>
                        <th style="width:11%;">Merek</th>
                        <th style="width:13%;">Serial Number</th>
                        <th style="width:11%;">Harga</th>
                        <th style="width:8%;">Kondisi</th>
                        <th style="width:8%;">Status</th>
                        <th style="width:9%;">Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($assets as $a): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= sanitize($a['kode_aset']) ?></td>
                        <td><?= sanitize($a['nama_barang']) ?></td>
                        <td><?= sanitize($a['nama_kategori'] ?? '-') ?></td>
                        <td><?= sanitize($a['merek'] ?? '-') ?></td>
                        <td><?= sanitize($a['serial_number'] ?? '-') ?></td>
                        <td class="text-right"><?= formatRupiah($a['harga']) ?></td>
                        <td class="text-center"><?= sanitize($a['kondisi'] ?? '-') ?></td>
                        <td class="text-center"><?= sanitize($a['status'] ?? '-') ?></td>
                        <td><?= sanitize($a['nama_lokasi'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Tanda Tangan -->
        <div class="ttd-wrapper">
            <div class="ttd-box">
                <p class="jabatan">Mengetahui,</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p class="nama"><?= sanitize(NAMA_SEKOLAH) ?></p>
            </div>
            <div class="ttd-box">
                <p class="jabatan"><?= sanitize(KOTA_SEKOLAH) ?>, <?= formatTanggal(date('Y-m-d')) ?></p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p class="nama">Petugas</p>
            </div>
        </div>

        <div class="footer-page">
            Halaman dicetak dari Sistem Inventaris Lab Komputer
        </div>
    </div>
</body>
</html>
