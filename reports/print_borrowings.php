<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/functions.php';

$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

$db = db();

if ($dari && $sampai) {
    $stmt = $db->prepare("SELECT b.*, a.kode_aset, a.nama_barang, u.nama as nama_peminjam FROM borrowings b JOIN assets a ON b.asset_id = a.id JOIN users u ON b.user_id = u.id WHERE b.tanggal_pinjam BETWEEN ? AND ? ORDER BY b.id DESC");
    $stmt->bind_param('ss', $dari, $sampai);
} else {
    $stmt = $db->prepare("SELECT b.*, a.kode_aset, a.nama_barang, u.nama as nama_peminjam FROM borrowings b JOIN assets a ON b.asset_id = a.id JOIN users u ON b.user_id = u.id ORDER BY b.id DESC");
}
$stmt->execute();
$result = $stmt->get_result();
$borrowings = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman - <?= sanitize(NAMA_SEKOLAH) ?></title>
    <style>
        @page {
            size: A4 portrait;
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
            display: flex;
            justify-content: space-between;
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
                max-width: 210mm;
                margin: 0 auto;
                background: #fff;
                padding: 18mm;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                min-height: 297mm;
            }
        }
        @media print and (orientation: portrait) {
            .print-wrapper { padding: 0; }
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
            <h3>LAPORAN DATA PEMINJAMAN</h3>
        </div>

        <div class="info-cetak">
            <span>Periode: <?= $dari && $sampai ? formatTanggal($dari) . ' s.d. ' . formatTanggal($sampai) : 'Semua Data' ?></span>
            <span>Dicetak: <?= formatTanggal(date('Y-m-d')) ?></span>
        </div>

        <?php if (empty($borrowings)): ?>
            <p style="text-align:center;padding:40px 0;color:#666;font-size:11pt;">Tidak ada data peminjaman</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:4%;">No</th>
                        <th style="width:10%;">Kode Aset</th>
                        <th style="width:16%;">Nama Aset</th>
                        <th style="width:15%;">Peminjam</th>
                        <th style="width:12%;">Tgl Pinjam</th>
                        <th style="width:12%;">Rencana Kembali</th>
                        <th style="width:12%;">Tgl Kembali</th>
                        <th style="width:10%;">Status</th>
                        <th style="width:9%;">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($borrowings as $b): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= sanitize($b['kode_aset']) ?></td>
                        <td><?= sanitize($b['nama_barang']) ?></td>
                        <td><?= sanitize($b['nama_peminjam']) ?></td>
                        <td class="text-center"><?= formatTanggal($b['tanggal_pinjam']) ?></td>
                        <td class="text-center"><?= formatTanggal($b['rencana_kembali']) ?></td>
                        <td class="text-center"><?= !empty($b['tanggal_kembali']) ? formatTanggal($b['tanggal_kembali']) : '-' ?></td>
                        <td class="text-center"><?= sanitize($b['status'] ?? '-') ?></td>
                        <td class="text-right"><?= formatRupiah($b['denda'] ?? 0) ?></td>
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
