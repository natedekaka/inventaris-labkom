<?php
session_start();

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/dashboard/stats.php';

App::requireLogin();

$role = $_SESSION['role'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;

$title = 'Dashboard';

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
<?php if (in_array($role, ['viewer', 'user'])): ?>
    <?php
    $totalAset = getTotalAssets();
    $myBorrowingsCount = getUserBorrowingsCount($userId);
    $myOverdueCount = getUserOverdueCount($userId);
    $myRecentBorrowings = getUserRecentBorrowings($userId, 5);
    ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Aset</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?= $totalAset ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-boxes text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Peminjaman Saya</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?= $myBorrowingsCount ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <i class="fas fa-handshake text-2xl text-yellow-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Overdue Saya</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?= $myOverdueCount ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-white shadow-md mb-8">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h4 class="text-lg font-semibold text-gray-800">Riwayat Peminjaman Saya</h4>
            <a href="/borrowings/peminjaman.php" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i>Pinjam Aset
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Aset</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Pinjam</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Rencana Kembali</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($myRecentBorrowings)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">Belum ada peminjaman</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($myRecentBorrowings as $b): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($b['kode_aset']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($b['nama_aset']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($b['tanggal_pinjam']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($b['rencana_kembali']) ?></td>
                        <td class="py-4 px-6">
                            <span class="badge <?= $b['status'] === 'dipinjam' ? 'badge-warning' : ($b['status'] === 'dikembalikan' ? 'badge-success' : 'badge-ghost') ?>">
                                <?= sanitize($b['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php
    $totalAset = getTotalAssets();
    $asetBaik = getAssetsByConditionCount('baik');
    $asetDipinjam = getBorrowedAssets();
    $asetRusak = getAssetsByConditionCount('rusak');
    $asetTerbaru = getRecentAssets(5);
    $overdueBorrowings = getOverdueBorrowings();
    $categoryData = getAssetsByCategory();
    $conditionData = getAssetsByConditionChart();
    $borrowingData = getMonthlyBorrowings(6);
    $totalNilai = getTotalNilaiAset();
    $biayaMaintTahun = getTotalBiayaMaintenanceTahunIni();
    $conditionByCategory = getAssetsConditionByCategory();
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Aset</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?= $totalAset ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-boxes text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Baik</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?= $asetBaik ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Dipinjam</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?= $asetDipinjam ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <i class="fas fa-handshake text-2xl text-yellow-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Rusak</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1"><?= $asetRusak ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Kondisi Barang per Kategori -->
    <div class="card bg-white shadow-md p-6 mb-8">
        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-primary mr-2"></i> Kondisi Barang per Kategori
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="kondisiTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="py-3 px-4 text-xs font-medium text-center text-gray-500 uppercase tracking-wider">Baik</th>
                        <th class="py-3 px-4 text-xs font-medium text-center text-gray-500 uppercase tracking-wider">Rusak Ringan</th>
                        <th class="py-3 px-4 text-xs font-medium text-center text-gray-500 uppercase tracking-wider">Rusak Berat</th>
                        <th class="py-3 px-4 text-xs font-medium text-center text-gray-500 uppercase tracking-wider bg-gray-100">Total</th>
                        <th class="py-3 px-4 text-xs font-medium text-center text-gray-500 uppercase tracking-wider">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php 
                    $grandBaik = 0; $grandRingan = 0; $grandBerat = 0; $grandTotal = 0;
                    foreach ($conditionByCategory as $row): 
                        $grandBaik += $row['baik'];
                        $grandRingan += $row['rusak_ringan'];
                        $grandBerat += $row['rusak_berat'];
                        $grandTotal += $row['total'];
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm font-medium text-gray-900"><?= sanitize($row['kategori']) ?></td>
                        <td class="py-3 px-4 text-sm text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <?= $row['baik'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <?= $row['rusak_ringan'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <?= $row['rusak_berat'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-center font-bold text-gray-900 bg-gray-50">
                            <?= $row['total'] ?>
                        </td>
                        <td class="py-3 px-4 text-sm text-center">
                            <button onclick="toggleDetail(this)" class="text-blue-600 hover:text-blue-800 text-xs font-medium focus:outline-none">
                                <i class="fas fa-chevron-down mr-1"></i> Lihat
                            </button>
                        </td>
                    </tr>
                    <tr class="detail-row hidden bg-gray-50">
                        <td colspan="6" class="py-3 px-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="font-semibold text-green-700 mb-1"><i class="fas fa-check-circle mr-1"></i>Baik (<?= $row['baik'] ?>)</p>
                                    <?php if (!empty($row['baik_items'])): ?>
                                        <ul class="list-disc list-inside text-gray-600 space-y-0.5">
                                        <?php foreach ($row['baik_items'] as $item): ?>
                                            <li><?= sanitize($item) ?></li>
                                        <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-gray-400 italic">-</p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-yellow-700 mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Rusak Ringan (<?= $row['rusak_ringan'] ?>)</p>
                                    <?php if (!empty($row['rusak_ringan_items'])): ?>
                                        <ul class="list-disc list-inside text-gray-600 space-y-0.5">
                                        <?php foreach ($row['rusak_ringan_items'] as $item): ?>
                                            <li><?= sanitize($item) ?></li>
                                        <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-gray-400 italic">-</p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-red-700 mb-1"><i class="fas fa-times-circle mr-1"></i>Rusak Berat (<?= $row['rusak_berat'] ?>)</p>
                                    <?php if (!empty($row['rusak_berat_items'])): ?>
                                        <ul class="list-disc list-inside text-gray-600 space-y-0.5">
                                        <?php foreach ($row['rusak_berat_items'] as $item): ?>
                                            <li><?= sanitize($item) ?></li>
                                        <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-gray-400 italic">-</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 font-bold">
                        <td class="py-3 px-4 text-sm text-gray-900">TOTAL</td>
                        <td class="py-3 px-4 text-sm text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-200 text-green-900">
                                <?= $grandBaik ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-900">
                                <?= $grandRingan ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-200 text-red-900">
                                <?= $grandBerat ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-center text-gray-900 bg-gray-200"><?= $grandTotal ?></td>
                        <td class="bg-gray-200"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script>
    function toggleDetail(btn) {
        var row = btn.closest('tr').nextElementSibling;
        while (row && row.classList.contains('detail-row')) {
            var isHidden = row.classList.contains('hidden');
            if (isHidden) {
                row.classList.remove('hidden');
                btn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Sembunyi';
            } else {
                row.classList.add('hidden');
                btn.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Lihat';
            }
            break;
        }
    }
    </script>

    <?php if ($totalNilai > 0 || $biayaMaintTahun > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <?php if ($totalNilai > 0): ?>
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Nilai Aset</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= formatRupiah($totalNilai) ?></p>
                </div>
                <div class="bg-emerald-100 p-3 rounded-lg">
                    <i class="fas fa-money-bill-wave text-2xl text-emerald-600"></i>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($biayaMaintTahun > 0): ?>
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Biaya Maintenance Tahun Ini</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= formatRupiah($biayaMaintTahun) ?></p>
                </div>
                <div class="bg-orange-100 p-3 rounded-lg">
                    <i class="fas fa-tools text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="card bg-white shadow-md p-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Aset per Kategori</h4>
            <canvas id="categoryChart"></canvas>
        </div>
        <div class="card bg-white shadow-md p-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Aset per Kondisi</h4>
            <div class="flex justify-center">
                <canvas id="conditionChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <div class="card bg-white shadow-md p-6 mb-8">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Peminjaman Bulanan</h4>
        <canvas id="borrowingChart"></canvas>
    </div>

    <?php if (!empty($overdueBorrowings)): 
        $totalOverdue = count($overdueBorrowings);
        $criticalOverdue = 0;
        $severeOverdue = 0;
        foreach ($overdueBorrowings as $ob) {
            if ($ob['hari_terlambat'] >= 7) $criticalOverdue++;
            elseif ($ob['hari_terlambat'] >= 3) $severeOverdue++;
        }
    ?>
    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-8 <?= $criticalOverdue > 0 ? 'border-red-600 bg-red-100' : ($severeOverdue > 0 ? 'border-orange-500 bg-orange-50' : '') ?>">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full <?= $criticalOverdue > 0 ? 'bg-red-200' : 'bg-red-100' ?> flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle <?= $criticalOverdue > 0 ? 'text-red-600' : 'text-red-500' ?> text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold <?= $criticalOverdue > 0 ? 'text-red-800' : 'text-red-700' ?>">
                        <?= $totalOverdue ?> Peminjaman Terlambat
                    </h4>
                    <p class="<?= $criticalOverdue > 0 ? 'text-red-600' : 'text-red-500' ?> text-sm mt-1">
                        <?php if ($criticalOverdue > 0): ?>
                            ⚠️ <?= $criticalOverdue ?> aset terlambat lebih dari 7 hari — <strong>segera tindak lanjuti!</strong>
                        <?php elseif ($severeOverdue > 0): ?>
                            ⏰ <?= $severeOverdue ?> aset terlambat antara 3-7 hari
                        <?php else: ?>
                            Seluruhnya terlambat kurang dari 3 hari
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <a href="/borrowings/?filter=dipinjam" class="hidden sm:inline-block bg-white border border-red-300 text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg text-sm font-medium transition duration-200">
                Kelola <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="<?= $criticalOverdue > 0 ? 'text-red-800 border-red-300' : 'text-red-700 border-red-200' ?> border-b">
                        <th class="py-2 px-3 font-medium">Peminjam</th>
                        <th class="py-2 px-3 font-medium">Aset</th>
                        <th class="py-2 px-3 font-medium">Rencana Kembali</th>
                        <th class="py-2 px-3 font-medium">Terlambat</th>
                        <th class="py-2 px-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-200">
                    <?php foreach ($overdueBorrowings as $overdue): 
                        $hari = $overdue['hari_terlambat'];
                        if ($hari >= 7) {
                            $severityClass = 'badge-error';
                            $severityLabel = $hari . ' hari';
                        } elseif ($hari >= 3) {
                            $severityClass = 'badge-warning';
                            $severityLabel = $hari . ' hari';
                        } else {
                            $severityClass = 'badge-ghost';
                            $severityLabel = $hari . ' hari';
                        }
                    ?>
                    <tr class="<?= $hari >= 7 ? 'text-red-800 bg-red-50/50' : ($hari >= 3 ? 'text-orange-700' : 'text-yellow-700') ?>">
                        <td class="py-2.5 px-3 font-medium"><?= sanitize($overdue['nama_peminjam']) ?></td>
                        <td class="py-2.5 px-3">
                            <div><?= sanitize($overdue['nama_aset']) ?></div>
                            <div class="text-xs opacity-75"><?= sanitize($overdue['kode_aset']) ?></div>
                        </td>
                        <td class="py-2.5 px-3"><?= formatTanggal($overdue['rencana_kembali']) ?></td>
                        <td class="py-2.5 px-3">
                            <span class="badge <?= $severityClass ?>">
                                <?= $severityLabel ?>
                            </span>
                        </td>
                        <td class="py-2.5 px-3">
                            <a href="/borrowings/pengembalian.php?id=<?= $overdue['id'] ?>" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                Proses <i class="fas fa-chevron-right ml-0.5"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3 flex justify-between items-center">
            <p class="text-xs <?= $criticalOverdue > 0 ? 'text-red-500' : 'text-gray-500' ?>">
                <i class="fas fa-info-circle mr-1"></i>Total denda diperkirakan: 
                <?php 
                    $totalDenda = 0;
                    foreach ($overdueBorrowings as $ob) {
                        $totalDenda += $ob['hari_terlambat'] * 1000;
                    }
                    echo formatRupiah($totalDenda);
                ?>
            </p>
            <a href="/borrowings/?filter=dipinjam" class="sm:hidden text-blue-600 hover:text-blue-800 text-xs font-medium">
                Kelola Semua <i class="fas fa-arrow-right ml-0.5"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'lab_assistant', 'operator'])): 
        $upcomingMaint = getUpcomingMaintenance(7);
        $overdueMaintCount = getOverdueMaintenanceCount();
    ?>
    <?php if (!empty($upcomingMaint)): ?>
    <div class="card bg-white shadow-md p-6 mb-8 <?= $overdueMaintCount > 0 ? 'border-l-4 border-red-500' : 'border-l-4 border-blue-500' ?>">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full <?= $overdueMaintCount > 0 ? 'bg-red-100' : 'bg-blue-100' ?> flex items-center justify-center mr-3">
                    <i class="fas fa-tools <?= $overdueMaintCount > 0 ? 'text-red-600' : 'text-blue-600' ?> text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800">Maintenance Terjadwal</h4>
                    <p class="text-sm text-gray-500">
                        <?php if ($overdueMaintCount > 0): ?>
                            ⚠️ <strong class="text-red-600"><?= $overdueMaintCount ?> maintenance terlewat!</strong> —
                        <?php endif; ?>
                        <?= count($upcomingMaint) ?> jadwal dalam 7 hari ke depan
                    </p>
                </div>
            </div>
            <a href="/maintenances/" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Kelola <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Aset</th>
                        <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Jenis</th>
                        <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Teknisi</th>
                        <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($upcomingMaint as $m): 
                        $hari = $m['hari_jatuh_tempo'];
                        if ($hari < 0) {
                            $badgeClass = 'badge-error';
                            $label = abs($hari) . ' hari terlewat';
                        } elseif ($hari === 0) {
                            $badgeClass = 'badge-warning';
                            $label = 'Hari ini';
                        } elseif ($hari <= 2) {
                            $badgeClass = 'badge-warning badge-outline';
                            $label = $hari . ' hari lagi';
                        } else {
                            $badgeClass = 'badge-info';
                            $label = $hari . ' hari lagi';
                        }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2.5 px-3">
                            <div class="font-medium text-gray-900"><?= sanitize($m['nama_aset']) ?></div>
                            <div class="text-xs text-gray-500"><?= sanitize($m['kode_aset']) ?></div>
                        </td>
                        <td class="py-2.5 px-3 text-gray-700"><?= formatTanggal($m['tanggal_maintenance']) ?></td>
                        <td class="py-2.5 px-3">
                            <span class="badge <?= $m['jenis'] === 'rutin' ? 'badge-success' : 'badge-secondary' ?>">
                                <?= ucfirst($m['jenis']) ?>
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-gray-700"><?= sanitize($m['teknisi'] ?? '-') ?></td>
                        <td class="py-2.5 px-3">
                            <span class="badge <?= $badgeClass ?>">
                                <?= $label ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="card bg-white shadow-md mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800">Aset Terbaru</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($asetTerbaru as $aset): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($aset['kode_aset']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($aset['nama_barang'] ?? '-') ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($aset['nama_kategori'] ?? '-') ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($aset['nama_lokasi'] ?? '-') ?></td>
                        <td class="py-4 px-6">
                            <span class="badge <?= $aset['status'] === 'tersedia' ? 'badge-success' : ($aset['status'] === 'dipinjam' ? 'badge-warning' : 'badge-error') ?>">
                                <?= sanitize($aset['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
</div>

<?php if (in_array($role, ['admin', 'lab_assistant', 'operator'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($categoryData['labels']) ?>,
            datasets: [{
                label: 'Jumlah Aset',
                data: <?= json_encode($categoryData['data']) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    new Chart(document.getElementById('conditionChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($conditionData['labels']) ?>,
            datasets: [{
                data: <?= json_encode($conditionData['data']) ?>,
                backgroundColor: <?= json_encode($conditionData['colors']) ?>,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true }
                }
            }
        }
    });

    new Chart(document.getElementById('borrowingChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($borrowingData['labels']) ?>,
            datasets: [{
                label: 'Peminjaman',
                data: <?= json_encode($borrowingData['data']) ?>,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.4,
                pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
