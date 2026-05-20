<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/functions.php';

$db = db();

$stmt = $db->prepare("SELECT m.*, a.nama_barang FROM maintenances m JOIN assets a ON m.asset_id = a.id ORDER BY m.id DESC");
$stmt->execute();
$result = $stmt->get_result();
$maintenances = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Maintenance - <?= sanitize(NAMA_SEKOLAH) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-white p-6">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900"><?= sanitize(NAMA_SEKOLAH) ?></h1>
            <p class="text-gray-600">Laporan Data Maintenance</p>
            <p class="text-sm text-gray-500">Dicetak pada: <?= formatTanggal(date('Y-m-d')) ?></p>
        </div>

        <div class="mb-4 no-print">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Cetak / Print</button>
        </div>

        <?php if (empty($maintenances)): ?>
            <p class="text-center text-gray-500 py-8">Tidak ada data</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">No</th>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">Nama Aset</th>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">Tanggal</th>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">Jenis</th>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">Deskripsi</th>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">Teknisi</th>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">Biaya</th>
                            <th class="border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php $no = 1; ?>
                        <?php foreach ($maintenances as $m): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= $no++ ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= sanitize($m['nama_barang']) ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= formatTanggal($m['tanggal_maintenance']) ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= sanitize($m['jenis'] ?? '-') ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= sanitize($m['deskripsi'] ?? '-') ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= sanitize($m['teknisi'] ?? '-') ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= formatRupiah($m['biaya'] ?? 0) ?></td>
                            <td class="border border-gray-300 px-3 py-2 text-sm text-gray-900"><?= sanitize($m['status'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
