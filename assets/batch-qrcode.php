<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Batch QR Code';
$db = db();

$selectedIds = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['asset_ids'])) {
    $selectedIds = array_map('intval', $_POST['asset_ids']);
} else {
    $assets = $db->query("SELECT id, kode_aset, nama_barang FROM assets ORDER BY kode_aset");
    
    ob_start();
    ?>
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6">Pilih Aset untuk Batch QR Code</h3>
            <form method="POST">
                <div class="mb-4">
                    <label class="flex items-center gap-2 mb-4">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Pilih Semua</span>
                    </label>
                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4">
                        <?php while ($a = $assets->fetch_assoc()): ?>
                        <label class="flex items-center gap-3 py-2 hover:bg-gray-50 rounded px-2">
                            <input type="checkbox" name="asset_ids[]" value="<?= $a['id'] ?>" class="asset-checkbox w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="text-sm text-gray-900"><?= sanitize($a['kode_aset'] . ' - ' . $a['nama_barang']) ?></span>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-qrcode mr-2"></i>Generate QR Codes
                    </button>
                    <a href="/assets/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.asset-checkbox').forEach(cb => cb.checked = this.checked);
        });
    </script>
    <?php
    $content = ob_get_clean();
    include '../views/layout.php';
    exit;
}

$placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
$types = str_repeat('i', count($selectedIds));
$stmt = $db->prepare("SELECT id, kode_aset, nama_barang FROM assets WHERE id IN ($placeholders) ORDER BY kode_aset");
$stmt->bind_param($types, ...$selectedIds);
$stmt->execute();
$result = $stmt->get_result();

ob_start();
?>
<div class="max-w-6xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-8 mb-8">
        <div class="flex justify-between items-center mb-6 no-print">
            <h3 class="text-xl font-bold text-gray-800">Batch QR Code (<?= $result->num_rows ?> aset)</h3>
            <div class="flex gap-2">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-print mr-2"></i>Print Semua
                </button>
                <a href="/assets/batch-qrcode.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Pilih Ulang</a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php 
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $allowedHosts = ['localhost', '127.0.0.1', $_SERVER['SERVER_NAME'] ?? ''];
            $host = in_array($_SERVER['HTTP_HOST'], $allowedHosts) ? $_SERVER['HTTP_HOST'] : 'localhost';
            while ($asset = $result->fetch_assoc()): 
                $qrUrl = "{$protocol}://{$host}/assets/detail.php?id={$asset['id']}";
                $qrImageUrl = generateQRCode("{$asset['kode_aset']}|{$asset['nama_barang']}|{$asset['id']}", 150);
            ?>
            <div class="border rounded-lg p-4 text-center break-inside-avoid">
                <img src="<?= $qrImageUrl ?>" alt="QR Code" class="mx-auto mb-2">
                <div class="text-xs text-gray-600"><?= sanitize($asset['kode_aset']) ?></div>
                <div class="text-xs font-medium text-gray-900 truncate"><?= sanitize($asset['nama_barang']) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<style>
@media print {
    nav, footer, .no-print { display: none !important; }
    body { background: white !important; }
    .grid { page-break-inside: avoid; }
}
</style>
<?php
$content = ob_get_clean();
include '../views/layout.php';
