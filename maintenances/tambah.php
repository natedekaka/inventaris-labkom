<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant']);

$title = 'Tambah Maintenance';
$db = db();

$assets = $db->query("SELECT * FROM assets ORDER BY nama_barang");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/maintenances/tambah.php');
    }
    $asset_id = (int)($_POST['asset_id'] ?? 0);
    $tanggal_maintenance = $_POST['tanggal_maintenance'] ?? date('Y-m-d');
    $jenis = $_POST['jenis'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $teknisi = $_POST['teknisi'] ?? '';
    $biaya = (float)($_POST['biaya'] ?? 0);
    $status = $_POST['status'] ?? 'belum';

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
            $stmt = $db->prepare("INSERT INTO maintenances (asset_id, tanggal_maintenance, jenis, deskripsi, teknisi, biaya, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isssdis', $asset_id, $tanggal_maintenance, $jenis, $deskripsi, $teknisi, $biaya, $status);

            if ($stmt->execute()) {
                if ($status === 'selesai') {
                    $update_asset = $db->prepare("UPDATE assets SET kondisi = 'baik' WHERE id = ?");
                    $update_asset->bind_param('i', $asset_id);
                    $update_asset->execute();
                } else {
                    $update_asset = $db->prepare("UPDATE assets SET status = 'perbaikan' WHERE id = ?");
                    $update_asset->bind_param('i', $asset_id);
                    $update_asset->execute();
                }

                App::setFlash('Maintenance berhasil ditambahkan', 'success');
                App::redirect('/maintenances/');
            } else {
                App::setFlash('Gagal menambahkan maintenance', 'danger');
            }
        }
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Tambah Maintenance</h3>
        <form method="POST">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Aset</label>
                <select name="asset_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Pilih Aset</option>
                    <?php while ($a = $assets->fetch_assoc()): ?>
                    <option value="<?= $a['id'] ?>"><?= sanitize($a['kode_aset'] . ' - ' . $a['nama_barang']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Maintenance</label>
                <input type="date" name="tanggal_maintenance" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Maintenance</label>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="jenis" value="rutin" id="rutin" required>
                        <label class="text-sm text-gray-700" for="rutin">Rutin</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="jenis" value="perbaikan" id="perbaikan" required>
                        <label class="text-sm text-gray-700" for="perbaikan">Perbaikan</label>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="deskripsi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="3" required><?= isset($_POST['deskripsi']) ? sanitize($_POST['deskripsi']) : '' ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teknisi</label>
                    <input type="text" name="teknisi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biaya</label>
                    <input type="number" name="biaya" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="0" step="0.01">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="status" value="belum" id="belum" checked>
                        <label class="text-sm text-gray-700" for="belum">Belum</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" type="radio" name="status" value="selesai" id="selesai">
                        <label class="text-sm text-gray-700" for="selesai">Selesai</label>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Simpan</button>
                <a href="/maintenances/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
