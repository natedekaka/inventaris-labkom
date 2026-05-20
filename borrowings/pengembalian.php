<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant', 'guru']);

$title = 'Pengembalian Aset';
$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT b.*, a.nama_barang as nama_aset, a.kode_aset, u.nama as nama_peminjam FROM borrowings b JOIN assets a ON b.asset_id = a.id JOIN users u ON b.user_id = u.id WHERE b.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$borrow = $result->fetch_assoc();

if (!$borrow || !in_array($borrow['status'], ['dipinjam', 'approved'])) {
    App::setFlash('Data peminjaman tidak valid', 'danger');
    App::redirect('/borrowings/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_kembali = $_POST['tanggal_kembali'] ?? date('Y-m-d');
    $kondisi_saat_kembali = $_POST['kondisi_saat_kembali'] ?? 'baik';

    $rencana_kembali = new DateTime($borrow['rencana_kembali']);
    $tanggal_kembali_dt = new DateTime($tanggal_kembali);
    $denda = 0;

    if ($tanggal_kembali_dt > $rencana_kembali) {
        $interval = $rencana_kembali->diff($tanggal_kembali_dt);
        $days_late = $interval->days;
        $denda = $days_late * 1000;
    }

    $update_stmt = $db->prepare("UPDATE borrowings SET tanggal_kembali = ?, kondisi_saat_kembali = ?, status = 'dikembalikan', denda = ? WHERE id = ?");
    $update_stmt->bind_param('ssdi', $tanggal_kembali, $kondisi_saat_kembali, $denda, $id);

    if ($update_stmt->execute()) {
        $update_asset = $db->prepare("UPDATE assets SET status = 'tersedia', kondisi = ? WHERE id = ?");
        $update_asset->bind_param('si', $kondisi_saat_kembali, $borrow['asset_id']);
        $update_asset->execute();

        logAssetAction($borrow['asset_id'], $_SESSION['user_id'], 'returned', ['denda' => $denda]);

        App::setFlash('Aset berhasil dikembalikan' . ($denda > 0 ? ' dengan denda Rp ' . number_format($denda, 0, ',', '.') : ''), 'success');
        App::redirect('/borrowings/');
    } else {
        App::setFlash('Gagal mengembalikan aset', 'danger');
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Pengembalian Aset</h3>

        <div class="mb-6">
            <table class="w-full border-collapse">
                <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Kode Aset</th><td class="py-3 px-4 text-sm text-gray-900">: <?= sanitize($borrow['kode_aset']) ?></td></tr>
                <tr class="border-b"><th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Nama Aset</th><td class="py-3 px-4 text-sm text-gray-900">: <?= sanitize($borrow['nama_aset']) ?></td></tr>
                <tr class="border-b"><th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Peminjam</th><td class="py-3 px-4 text-sm text-gray-900">: <?= sanitize($borrow['nama_peminjam']) ?></td></tr>
                <tr class="border-b"><th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Tanggal Pinjam</th><td class="py-3 px-4 text-sm text-gray-900">: <?= formatTanggal($borrow['tanggal_pinjam']) ?></td></tr>
                <tr class="border-b"><th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Rencana Kembali</th><td class="py-3 px-4 text-sm text-gray-900">: <?= formatTanggal($borrow['rencana_kembali']) ?></td></tr>
                <tr class="border-b"><th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Keperluan</th><td class="py-3 px-4 text-sm text-gray-900">: <?= sanitize($borrow['keperluan']) ?></td></tr>
            </table>
        </div>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kembali</label>
                <input type="date" name="tanggal_kembali" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kondisi Saat Kembali</label>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="kondisi_saat_kembali" value="baik" id="baik" checked>
                        <label class="text-sm text-gray-700" for="baik">Baik</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="kondisi_saat_kembali" value="rusak_ringan" id="rusak_ringan">
                        <label class="text-sm text-gray-700" for="rusak_ringan">Rusak Ringan</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="kondisi_saat_kembali" value="rusak_berat" id="rusak_berat">
                        <label class="text-sm text-gray-700" for="rusak_berat">Rusak Berat</label>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">Kembalikan</button>
                <a href="/borrowings/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
