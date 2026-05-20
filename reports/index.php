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
        <h4 class="text-lg font-bold text-gray-800 mb-4">Laporan Peminjaman per Periode</h4>
        <form class="flex gap-3 items-end flex-wrap" onsubmit="return validatePeriod(this)">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari Tanggal</label>
                <input type="date" name="dari" id="dari_pinjam" class="px-3 py-2 border rounded-lg text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai" id="sampai_pinjam" class="px-3 py-2 border rounded-lg text-sm" required>
            </div>
            <a href="#" onclick="exportBorrowings()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm inline-block">
                <i class="fas fa-file-csv mr-1"></i>Export CSV
            </a>
            <a href="#" onclick="printBorrowings()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm inline-block" target="_blank">
                <i class="fas fa-file-pdf mr-1"></i>Cetak PDF
            </a>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h4 class="text-lg font-bold text-gray-800 mb-4">Laporan Maintenance per Periode</h4>
        <form class="flex gap-3 items-end flex-wrap" onsubmit="return validatePeriod(this)">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari Tanggal</label>
                <input type="date" name="dari" id="dari_maintenance" class="px-3 py-2 border rounded-lg text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai" id="sampai_maintenance" class="px-3 py-2 border rounded-lg text-sm" required>
            </div>
            <a href="#" onclick="exportMaintenances()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm inline-block">
                <i class="fas fa-file-csv mr-1"></i>Export CSV
            </a>
            <a href="#" onclick="printMaintenances()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm inline-block">
                <i class="fas fa-file-pdf mr-1"></i>Cetak PDF
            </a>
        </form>
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

<script>
function validatePeriod(form) {
    var dari = form.querySelector('[name="dari"]').value;
    var sampai = form.querySelector('[name="sampai"]').value;
    if (sampai < dari) {
        alert('Tanggal Sampai harus lebih besar atau sama dengan Dari Tanggal');
        return false;
    }
    return true;
}

function exportBorrowings() {
    var dari = document.getElementById('dari_pinjam').value;
    var sampai = document.getElementById('sampai_pinjam').value;
    if (!dari || !sampai) { alert('Pilih periode terlebih dahulu'); return; }
    if (sampai < dari) { alert('Tanggal Sampai harus lebih besar atau sama dengan Dari Tanggal'); return; }
    window.location.href = 'export_borrowings.php?dari=' + dari + '&sampai=' + sampai;
}

function printBorrowings() {
    var dari = document.getElementById('dari_pinjam').value;
    var sampai = document.getElementById('sampai_pinjam').value;
    if (!dari || !sampai) { alert('Pilih periode terlebih dahulu'); return; }
    if (sampai < dari) { alert('Tanggal Sampai harus lebih besar atau sama dengan Dari Tanggal'); return; }
    window.open('print_borrowings.php?dari=' + dari + '&sampai=' + sampai, '_blank');
}

function exportMaintenances() {
    var dari = document.getElementById('dari_maintenance').value;
    var sampai = document.getElementById('sampai_maintenance').value;
    if (!dari || !sampai) { alert('Pilih periode terlebih dahulu'); return; }
    if (sampai < dari) { alert('Tanggal Sampai harus lebih besar atau sama dengan Dari Tanggal'); return; }
    window.location.href = 'export_maintenances.php?dari=' + dari + '&sampai=' + sampai;
}

function printMaintenances() {
    var dari = document.getElementById('dari_maintenance').value;
    var sampai = document.getElementById('sampai_maintenance').value;
    if (!dari || !sampai) { alert('Pilih periode terlebih dahulu'); return; }
    if (sampai < dari) { alert('Tanggal Sampai harus lebih besar atau sama dengan Dari Tanggal'); return; }
    window.open('print_maintenances.php?dari=' + dari + '&sampai=' + sampai, '_blank');
}
</script>
<?php
$content = ob_get_clean();
include '../views/layout.php';
