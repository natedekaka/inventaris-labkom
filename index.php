<?php
session_start();

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/dashboard/stats.php';

$totalAset = getTotalAssets();
$asetBaik = getAssetsByConditionCount('baik');
$asetDipinjam = getBorrowedAssets();
$asetRusak = getAssetsByConditionCount('rusak');
$asetTerbaru = getRecentAssets(5);
$overdueBorrowings = getOverdueBorrowings();

// Chart data
$categoryData = getAssetsByCategory();
$conditionData = getAssetsByConditionChart();
$borrowingData = getMonthlyBorrowings(6);

$title = 'Dashboard';

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <!-- Stat Cards -->
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

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Aset per Kategori</h4>
            <canvas id="categoryChart"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Aset per Kondisi</h4>
            <div class="flex justify-center">
                <canvas id="conditionChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Borrowing Chart -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Peminjaman Bulanan</h4>
        <canvas id="borrowingChart"></canvas>
    </div>

    <!-- Overdue Borrowings Alert -->
    <?php if (!empty($overdueBorrowings)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-8">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
            <div>
                <h4 class="font-semibold text-red-800">Peminjaman Terlambat</h4>
                <p class="text-red-700 text-sm mt-1">Terdapat <?= count($overdueBorrowings) ?> peminjaman yang melewati batas waktu pengembalian.</p>
            </div>
        </div>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-red-800 border-b border-red-200">
                        <th class="py-2 px-3 font-medium">Peminjam</th>
                        <th class="py-2 px-3 font-medium">Aset</th>
                        <th class="py-2 px-3 font-medium">Rencana Kembali</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-200">
                    <?php foreach ($overdueBorrowings as $overdue): ?>
                    <tr class="text-red-700">
                        <td class="py-2 px-3"><?= sanitize($overdue['nama_peminjam']) ?></td>
                        <td class="py-2 px-3"><?= sanitize($overdue['nama_aset']) ?></td>
                        <td class="py-2 px-3"><?= formatTanggal($overdue['rencana_kembali']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Assets Table -->
    <div class="bg-white rounded-xl shadow-md mb-8">
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
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $aset['status'] === 'tersedia' ? 'bg-green-100 text-green-800' : ($aset['status'] === 'dipinjam' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                <?= sanitize($aset['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
