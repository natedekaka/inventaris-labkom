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
            margin: 12mm 15mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5pt;
            line-height: 1.5;
            color: #000;
        }
        .no-print { display: none !important; }
        .print-wrapper {
            position: relative;
        }
        .kop-surat {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 3px solid #1a1a2e;
            position: relative;
        }
        .kop-surat .logo-sekolah {
            width: 55px;
            height: 55px;
            border: 2px solid #1a1a2e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px auto;
            font-size: 7pt;
            font-weight: bold;
            color: #1a1a2e;
            line-height: 1.2;
            padding: 4px;
            text-align: center;
        }
        .kop-surat h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 2px 0;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #1a1a2e;
        }
        .kop-surat .alamat {
            font-size: 9pt;
            color: #444;
            margin: 2px 0;
        }
        .kop-surat .kontak {
            font-size: 8pt;
            color: #666;
            margin: 1px 0;
        }
        .kop-surat .garis-bawah {
            width: 60%;
            height: 1px;
            background: #1a1a2e;
            margin: 4px auto 0 auto;
        }
        .judul-laporan {
            text-align: center;
            margin: 14px 0 10px 0;
        }
        .judul-laporan h3 {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            text-underline-offset: 3px;
            color: #1a1a2e;
        }
        .info-cetak {
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
            margin-bottom: 8px;
            color: #555;
            padding: 4px 2px;
            border-bottom: 1px dashed #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        thead { display: table-header-group; }
        th {
            border: 1px solid #1a1a2e;
            padding: 5px 4px;
            font-weight: bold;
            text-align: center;
            background: #1a1a2e !important;
            color: #fff !important;
            font-size: 8pt;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        td {
            border: 1px solid #999;
            padding: 3px 4px;
            vertical-align: middle;
        }
        tr { page-break-inside: avoid; }
        tr:nth-child(even) td {
            background-color: #f2f4f8 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        tr:hover td {
            background-color: #e8ecf4 !important;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td {
            font-weight: bold;
            background: #d4d8e0 !important;
            border-top: 2px solid #1a1a2e;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .ringkasan {
            margin-top: 12px;
            display: flex;
            gap: 20px;
            font-size: 9pt;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fafbfc;
        }
        .ringkasan span {
            font-weight: bold;
            color: #1a1a2e;
        }
        .ttd-wrapper {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .ttd-box {
            text-align: center;
            width: 45%;
        }
        .ttd-box p { margin: 2px 0; font-size: 9pt; }
        .ttd-box .jabatan {
            margin-bottom: 4px;
            font-weight: bold;
        }
        .ttd-box .garis-ttd {
            width: 200px;
            border-top: 1px solid #000;
            margin: 0 auto;
        }
        .ttd-box .nama {
            font-weight: bold;
            margin-top: 2px;
        }
        .ttd-box .nip {
            font-size: 8pt;
            color: #555;
        }
        .footer-page {
            text-align: center;
            font-size: 7.5pt;
            color: #888;
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid #ddd;
            font-style: italic;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .print-wrapper { padding: 0; }
            .no-print { display: none !important; }
        }
        @media screen {
            body { background: #e8ecf1; padding: 30px; }
            .print-wrapper {
                max-width: 297mm;
                margin: 0 auto;
                background: #fff;
                padding: 15mm;
                box-shadow: 0 4px 20px rgba(0,0,0,0.12);
                border-radius: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="print-wrapper">
        <div class="no-print" style="text-align:right;margin-bottom:10px;">
            <button onclick="window.print()" style="background:#1a1a2e;color:#fff;border:none;padding:8px 22px;border-radius:4px;font-size:10pt;cursor:pointer;font-family:'Times New Roman',serif;">
                🖨 Cetak / Print
            </button>
            <button onclick="window.close()" style="background:#888;color:#fff;border:none;padding:8px 22px;border-radius:4px;font-size:10pt;cursor:pointer;font-family:'Times New Roman',serif;margin-left:6px;">
                ✕ Tutup
            </button>
        </div>

        <div class="kop-surat">
            <div class="logo-sekolah">LOGO<br>SEKOLAH</div>
            <h1><?= sanitize(NAMA_SEKOLAH) ?></h1>
            <p class="alamat"><?= sanitize(ALAMAT_SEKOLAH) ?></p>
            <p class="kontak"><?= sanitize(KOTA_SEKOLAH) ?> | Telp: <?= defined('TELP_SEKOLAH') ? sanitize(TELP_SEKOLAH) : '-' ?> | Email: <?= defined('EMAIL_SEKOLAH') ? sanitize(EMAIL_SEKOLAH) : '-' ?></p>
            <div class="garis-bawah"></div>
        </div>

        <div class="judul-laporan">
            <h3>LAPORAN DATA ASET</h3>
        </div>

        <div class="info-cetak">
            <span>Periode: Seluruh Data Aset</span>
            <span>Dicetak: <?= formatTanggal(date('Y-m-d')) ?> | Pukul: <?= date('H:i') ?> WIB</span>
        </div>

        <?php if (empty($assets)): ?>
            <p style="text-align:center;padding:40px 0;color:#666;font-size:11pt;">Tidak ada data aset</p>
        <?php else:
            $totalHarga = 0;
            $countBaik = 0; $countRusak = 0; $countDipinjam = 0;
            foreach ($assets as $a) {
                $totalHarga += $a['harga'];
                if ($a['kondisi'] === 'baik') $countBaik++;
                elseif ($a['kondisi'] === 'rusak') $countRusak++;
                if ($a['status'] === 'dipinjam') $countDipinjam++;
            }
        ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:3%;">No</th>
                        <th style="width:8%;">Kode Aset</th>
                        <th style="width:15%;">Nama Barang</th>
                        <th style="width:9%;">Kategori</th>
                        <th style="width:9%;">Merek</th>
                        <th style="width:10%;">Serial Number</th>
                        <th style="width:12%;">Harga</th>
                        <th style="width:7%;">Kondisi</th>
                        <th style="width:7%;">Status</th>
                        <th style="width:9%;">Lokasi</th>
                        <th style="width:11%;">Tgl. Peroleh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($assets as $a): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><strong><?= sanitize($a['kode_aset']) ?></strong></td>
                        <td><?= sanitize($a['nama_barang']) ?></td>
                        <td><?= sanitize($a['nama_kategori'] ?? '-') ?></td>
                        <td><?= sanitize($a['merek'] ?? '-') ?></td>
                        <td><?= sanitize($a['serial_number'] ?? '-') ?></td>
                        <td class="text-right"><?= formatRupiah($a['harga']) ?></td>
                        <td class="text-center"><?= sanitize($a['kondisi'] ?? '-') ?></td>
                        <td class="text-center"><?= sanitize($a['status'] ?? '-') ?></td>
                        <td><?= sanitize($a['nama_lokasi'] ?? '-') ?></td>
                        <td class="text-center"><?= !empty($a['tanggal_beli']) ? formatTanggal($a['tanggal_beli']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">TOTAL</td>
                        <td class="text-right"><?= formatRupiah($totalHarga) ?></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>

            <div class="ringkasan">
                <span>📦 Total Aset: <?= count($assets) ?> item</span>
                <span>✅ Kondisi Baik: <?= $countBaik ?></span>
                <span>🔧 Kondisi Rusak: <?= $countRusak ?></span>
                <span>📌 Sedang Dipinjam: <?= $countDipinjam ?></span>
                <span>💰 Total Nilai: <?= formatRupiah($totalHarga) ?></span>
            </div>
        <?php endif; ?>

        <div class="ttd-wrapper">
            <div class="ttd-box">
                <p class="jabatan">Mengetahui,</p>
                <p><?= sanitize(KOTA_SEKOLAH) ?>, <?= formatTanggal(date('Y-m-d')) ?></p>
                <p style="margin-bottom:50px;">&nbsp;</p>
                <div class="garis-ttd"></div>
                <p class="nama"><?= sanitize(NAMA_SEKOLAH) ?></p>
                <p class="nip">NIP. <?= defined('NIP_SEKOLAH') ? sanitize(NIP_SEKOLAH) : '-..........................-' ?></p>
            </div>
            <div class="ttd-box">
                <p class="jabatan">Petugas,</p>
                <p>&nbsp;</p>
                <p style="margin-bottom:50px;">&nbsp;</p>
                <div class="garis-ttd"></div>
                <p class="nama"><?= sanitize($_SESSION['nama'] ?? 'Petugas') ?></p>
                <p class="nip">NIP. ........................................</p>
            </div>
        </div>

        <div class="footer-page">
            <span>Dokumen ini dicetak secara elektronik dari <?= sanitize(NAMA_SEKOLAH) ?> &mdash; Sistem Inventaris Lab Komputer</span>
        </div>
    </div>
</body>
</html>
