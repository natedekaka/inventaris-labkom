<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'QR Code Aset';
$db = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
    App::setFlash('Aset tidak ditemukan', 'danger');
    App::redirect('/assets/');
}

$qrUrl = generateQRCode("http://{$_SERVER['HTTP_HOST']}/assets/detail.php?id={$asset['id']}", 300);

ob_start();
?>
<div class="max-w-2xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-8 mb-8 text-center">
        <div class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-qrcode text-white text-2xl"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">QR Code - <?= sanitize($asset['kode_aset']) ?></h3>
        <p class="text-gray-600 mb-6"><?= sanitize($asset['nama_barang']) ?></p>
        
        <div class="mb-6">
            <?php if ($asset['foto']): ?>
            <img src="../uploads/<?= $asset['foto'] ?>" class="max-w-full h-auto mx-auto mb-4" style="max-width: 200px;">
            <?php endif; ?>
            <img src="<?= $qrUrl ?>" alt="QR Code" class="mx-auto border p-4 rounded-lg">
        </div>

        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <table class="w-full">
                <tr><th class="text-left py-2 text-sm font-medium text-gray-700">Kode</th><td class="py-2 text-sm text-gray-900"><?= sanitize($asset['kode_aset']) ?></td></tr>
                <tr><th class="text-left py-2 text-sm font-medium text-gray-700">Nama</th><td class="py-2 text-sm text-gray-900"><?= sanitize($asset['nama_barang']) ?></td></tr>
                <tr><th class="text-left py-2 text-sm font-medium text-gray-700">Kondisi</th><td class="py-2 text-sm text-gray-900"><?= sanitize($asset['kondisi']) ?></td></tr>
            </table>
        </div>

        <div class="flex justify-center gap-2 no-print">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-print mr-2"></i>Print
            </button>
            <a href="detail.php?id=<?= $asset['id'] ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
                Kembali
            </a>
        </div>
    </div>
</div>

<style>
@media print {
    nav, footer, .no-print { display: none !important; }
    body { background: white !important; }
    .shadow-md { box-shadow: none !important; }
}
</style>
<?php
$content = ob_get_clean();
include '../views/layout.php';
