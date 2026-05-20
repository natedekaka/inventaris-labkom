<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant']);

$title = 'Edit Maintenance';
$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT m.*, a.nama_barang as nama_aset FROM maintenances m JOIN assets a ON m.asset_id = a.id WHERE m.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$maintenance = $result->fetch_assoc();

if (!$maintenance) {
    App::setFlash('Data maintenance tidak valid', 'danger');
    App::redirect('/maintenances/');
}

$assets = $db->query("SELECT * FROM assets ORDER BY nama_barang");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/maintenances/edit.php?id=' . $id);
    }
    $asset_id = (int)($_POST['asset_id'] ?? $maintenance['asset_id']);
    $tanggal_maintenance = $_POST['tanggal_maintenance'] ?? $maintenance['tanggal_maintenance'];
    $jenis = $_POST['jenis'] ?? $maintenance['jenis'];
    $deskripsi = $_POST['deskripsi'] ?? '';
    $teknisi = $_POST['teknisi'] ?? '';
    $biaya = (float)($_POST['biaya'] ?? 0);
    $status = $_POST['status'] ?? $maintenance['status'];

    if (empty($jenis) || empty($deskripsi) || empty($teknisi)) {
        App::setFlash('Field wajib harus diisi', 'danger');
    } else {
        $check_asset = $db->prepare("SELECT id FROM assets WHERE id = ?");
        $check_asset->bind_param('i', $asset_id);
        $check_asset->execute();
        $asset_result = $check_asset->get_result();

        if ($asset_result->num_rows === 0) {
            App::setFlash('Aset tidak valid', 'danger');
        } else {
            $update_stmt = $db->prepare("UPDATE maintenances SET asset_id = ?, tanggal_maintenance = ?, jenis = ?, deskripsi = ?, teknisi = ?, biaya = ?, status = ? WHERE id = ?");
            $update_stmt->bind_param('isssdisi', $asset_id, $tanggal_maintenance, $jenis, $deskripsi, $teknisi, $biaya, $status, $id);

            if ($update_stmt->execute()) {
                if ($status === 'selesai') {
                    $update_asset = $db->prepare("UPDATE assets SET kondisi = 'baik', status = 'tersedia' WHERE id = ?");
                    $update_asset->bind_param('i', $asset_id);
                    $update_asset->execute();
                } else {
                    $update_asset = $db->prepare("UPDATE assets SET status = 'perbaikan' WHERE id = ?");
                    $update_asset->bind_param('i', $asset_id);
                    $update_asset->execute();
                }

                App::setFlash('Maintenance berhasil diperbarui', 'success');
                App::redirect('/maintenances/');
            } else {
                App::setFlash('Gagal memperbarui maintenance', 'danger');
            }
        }
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Edit Maintenance - <?= sanitize($maintenance['nama_aset']) ?></h3>
        <form method="POST">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Aset</label>
                <select name="asset_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Pilih Aset</option>
                    <?php while ($a = $assets->fetch_assoc()): ?>
                    <option value="<?= $a['id'] ?>" <?= $a['id'] == $maintenance['asset_id'] ? 'selected' : '' ?>><?= sanitize($a['kode_aset'] . ' - ' . $a['nama_barang']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Maintenance</label>
                <input type="date" name="tanggal_maintenance" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= $maintenance['tanggal_maintenance'] ?>" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Maintenance</label>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="jenis" value="rutin" id="rutin" <?= $maintenance['jenis'] === 'rutin' ? 'checked' : '' ?> required>
                        <label class="text-sm text-gray-700" for="rutin">Rutin</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="jenis" value="perbaikan" id="perbaikan" <?= $maintenance['jenis'] === 'perbaikan' ? 'checked' : '' ?> required>
                        <label class="text-sm text-gray-700" for="perbaikan">Perbaikan</label>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="deskripsi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="3" required><?= sanitize($maintenance['deskripsi']) ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teknisi</label>
                    <input type="text" name="teknisi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= sanitize($maintenance['teknisi']) ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biaya</label>
                    <input type="number" name="biaya" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= $maintenance['biaya'] ?>" step="0.01">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="status" value="belum" id="belum" <?= $maintenance['status'] === 'belum' ? 'checked' : '' ?>>
                        <label class="text-sm text-gray-700" for="belum">Belum</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="status" value="selesai" id="selesai" <?= $maintenance['status'] === 'selesai' ? 'checked' : '' ?>>
                        <label class="text-sm text-gray-700" for="selesai">Selesai</label>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Update</button>
                <a href="/maintenances/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
