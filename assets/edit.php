<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant']);

$title = 'Edit Aset';
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

$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori");
$locations = $db->query("SELECT * FROM locations ORDER BY nama_lokasi");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/assets/edit.php?id=' . $id);
    }
    $nama_barang = $_POST['nama_barang'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $merek = $_POST['merek'] ?? '';
    $model = $_POST['model'] ?? '';
    $serial_number = !empty($_POST['serial_number']) ? $_POST['serial_number'] : null;
    $spesifikasi = $_POST['spesifikasi'] ?? '';
    $harga = (float)($_POST['harga'] ?? 0);
    if ($harga < 0) $harga = 0;
    $kondisi = $_POST['kondisi'] ?? '';
    if (!in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'])) $kondisi = $asset['kondisi'];
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['tersedia', 'dipinjam', 'perbaikan'])) $status = $asset['status'];
    $location_id = (int)($_POST['location_id'] ?? 0);

    $foto = $asset['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_types)) {
            App::setFlash('Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, atau WEBP.', 'danger');
            App::redirect('/assets/edit.php?id=' . $id);
        }

        if ($_FILES['foto']['size'] > 2097152) {
            App::setFlash('Ukuran file maksimal 2MB', 'danger');
            App::redirect('/assets/edit.php?id=' . $id);
        }

        $check = getimagesize($_FILES['foto']['tmp_name']);
        if ($check === false) {
            App::setFlash('File bukan gambar yang valid', 'danger');
            App::redirect('/assets/edit.php?id=' . $id);
        }

        $foto = 'asset_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto);
    }

    $stmt = $db->prepare("UPDATE assets SET nama_barang = ?, category_id = ?, merek = ?, model = ?, serial_number = ?, spesifikasi = ?, harga = ?, kondisi = ?, status = ?, location_id = ?, foto = ? WHERE id = ?");
    $stmt->bind_param('sisssdsssisi', $nama_barang, $category_id, $merek, $model, $serial_number, $spesifikasi, $harga, $kondisi, $status, $location_id, $foto, $id);

    if ($stmt->execute()) {
        App::setFlash('Aset berhasil diupdate', 'success');
        App::redirect('/assets/');
    } else {
        App::setFlash('Gagal mengupdate aset', 'danger');
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Edit Aset - <?= sanitize($asset['kode_aset']) ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Aset</label>
                <input type="text" name="nama_barang" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= sanitize($asset['nama_barang']) ?>" required>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Pilih Kategori</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $asset['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['nama_kategori']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <select name="location_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Pilih Lokasi</option>
                        <?php while ($loc = $locations->fetch_assoc()): ?>
                        <option value="<?= $loc['id'] ?>" <?= $asset['location_id'] == $loc['id'] ? 'selected' : '' ?>><?= sanitize($loc['nama_lokasi']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Merek</label>
                    <input type="text" name="merek" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= sanitize($asset['merek']) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= sanitize($asset['model']) ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                <input type="text" name="serial_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= sanitize($asset['serial_number']) ?>">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Spesifikasi</label>
                <textarea name="spesifikasi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="3"><?= sanitize($asset['spesifikasi']) ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga</label>
                    <input type="number" name="harga" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= $asset['harga'] ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi</label>
                    <select name="kondisi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="baik" <?= $asset['kondisi'] === 'baik' ? 'selected' : '' ?>>Baik</option>
                        <option value="rusak_ringan" <?= $asset['kondisi'] === 'rusak_ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                        <option value="rusak_berat" <?= $asset['kondisi'] === 'rusak_berat' ? 'selected' : '' ?>>Rusak Berat</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="tersedia" <?= $asset['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="dipinjam" <?= $asset['status'] === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                    <option value="perbaikan" <?= $asset['status'] === 'perbaikan' ? 'selected' : '' ?>>Perbaikan</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                <?php if ($asset['foto']): ?>
                <p class="mb-2"><img src="../uploads/<?= $asset['foto'] ?>" class="max-w-full h-auto" style="max-width: 300px;"></p>
                <?php endif; ?>
                <input type="file" name="foto" class="w-full px-4 py-2 border border-gray-300 rounded-lg" accept="image/*">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Update</button>
                <a href="/assets/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
