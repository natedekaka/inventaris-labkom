<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Laporan Inventaris';
$db = db();

$totalAset = $db->query("SELECT COUNT(*) as total FROM assets")->fetch_assoc()['total'];
$totalNilai = $db->query("SELECT SUM(harga) as total FROM assets")->fetch_assoc()['total'];
$asetTersedia = $db->query("SELECT COUNT(*) as total FROM assets WHERE status = 'tersedia'")->fetch_assoc()['total'];
$asetDipinjam = $db->query("SELECT COUNT(*) as total FROM assets WHERE status = 'dipinjam'")->fetch_assoc()['total'];
$asetMaintenance = $db->query("SELECT COUNT(*) as total FROM assets WHERE status = 'perbaikan'")->fetch_assoc()['total'];

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <h3 class="text-xl font-bold text-gray-800 mb-6">Laporan Inventaris Lab Komputer</h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-sm font-medium text-gray-500 mb-2">Total Aset</h5>
            <h3 class="text-3xl font-bold text-gray-800"><?= $totalAset ?></h3>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-sm font-medium text-gray-500 mb-2">Total Nilai Aset</h5>
            <h3 class="text-3xl font-bold text-gray-800"><?= formatRupiah($totalNilai) ?></h3>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-sm font-medium text-gray-500 mb-4">Status Aset</h5>
            <p class="text-gray-600">Tersedia: <span class="font-semibold text-green-600"><?= $asetTersedia ?></span><br>
            Dipinjam: <span class="font-semibold text-yellow-600"><?= $asetDipinjam ?></span><br>
            Perbaikan: <span class="font-semibold text-red-600"><?= $asetMaintenance ?></span></p>
        </div>
    </div>

    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="export_assets.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200 inline-block">
            <i class="fas fa-file-csv mr-1"></i>Export CSV
        </a>
        <a href="print_assets.php" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200 inline-block">
            <i class="fas fa-file-pdf mr-1"></i>Cetak PDF
        </a>
        <a href="print_borrowings.php" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 inline-block">
            <i class="fas fa-file-pdf mr-1"></i>Cetak Peminjaman
        </a>
        <a href="print_maintenances.php" target="_blank" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition duration-200 inline-block">
            <i class="fas fa-file-pdf mr-1"></i>Cetak Maintenance
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h4 class="text-lg font-bold text-gray-800 mb-4">Kategori Aset</h4>
        <?php
        $kategoriResult = $db->query("SELECT c.nama_kategori as kategori, COUNT(*) as jumlah FROM assets a LEFT JOIN categories c ON a.category_id = c.id GROUP BY c.nama_kategori");
        ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($row = $kategoriResult->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['kategori']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= $row['jumlah'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
