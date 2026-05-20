<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant']);

$title = 'Tambah Aset';
$db = db();

$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori");
$locations = $db->query("SELECT * FROM locations ORDER BY nama_lokasi");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/assets/tambah.php');
    }
    $kode = generateKodeAset('INV');
    $nama_barang = $_POST['nama_barang'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $merek = $_POST['merek'] ?? '';
    $model = $_POST['model'] ?? '';
    $serial_number = !empty($_POST['serial_number']) ? $_POST['serial_number'] : null;
    $spesifikasi = $_POST['spesifikasi'] ?? '';
    $harga = (float)($_POST['harga'] ?? 0);
    if ($harga < 0) $harga = 0;
    $tanggal_beli = $_POST['tanggal_beli'] ?? date('Y-m-d');
    $kondisi = $_POST['kondisi'] ?? 'baik';
    if (!in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'])) $kondisi = 'baik';
    $status = $_POST['status'] ?? 'tersedia';
    if (!in_array($status, ['tersedia', 'dipinjam', 'perbaikan'])) $status = 'tersedia';
    $location_id = (int)($_POST['location_id'] ?? 0);
    $garansi_sampai = !empty($_POST['garansi_sampai']) ? $_POST['garansi_sampai'] : null;

    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_types)) {
            App::setFlash('Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, atau WEBP.', 'danger');
            App::redirect('/assets/tambah.php');
        }

        if ($_FILES['foto']['size'] > 2097152) {
            App::setFlash('Ukuran file maksimal 2MB', 'danger');
            App::redirect('/assets/tambah.php');
        }

        $check = getimagesize($_FILES['foto']['tmp_name']);
        if ($check === false) {
            App::setFlash('File bukan gambar yang valid', 'danger');
            App::redirect('/assets/tambah.php');
        }

        $foto = 'asset_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto);
    }

    $stmt = $db->prepare("
        INSERT INTO assets
        (kode_aset, nama_barang, category_id, merek, model, serial_number, spesifikasi, harga, tanggal_beli, kondisi, status, location_id, foto, garansi_sampai)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sisisssdsssisd',
        $kode, $nama_barang, $category_id, $merek, $model, $serial_number, $spesifikasi, $harga, $tanggal_beli, $kondisi, $status, $location_id, $foto, $garansi_sampai
    );

    if ($stmt->execute()) {
        App::setFlash('Aset berhasil ditambahkan', 'success');
        App::redirect('/assets/');
    } else {
        App::setFlash('Gagal menambahkan aset', 'danger');
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Tambah Aset</h3>
        <form method="POST" enctype="multipart/form-data">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Aset</label>
                <input type="text" name="nama_barang" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Pilih Kategori</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>"><?= sanitize($cat['nama_kategori']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <select name="location_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Pilih Lokasi</option>
                        <?php while ($loc = $locations->fetch_assoc()): ?>
                        <option value="<?= $loc['id'] ?>"><?= sanitize($loc['nama_lokasi']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Merek</label>
                    <input type="text" name="merek" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                <input type="text" name="serial_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Spesifikasi</label>
                <textarea name="spesifikasi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="3"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga</label>
                    <input type="number" name="harga" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Beli</label>
                    <input type="date" name="tanggal_beli" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Garansi Sampai</label>
                    <input type="date" name="garansi_sampai" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi</label>
                    <select name="kondisi" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="tersedia">Tersedia</option>
                        <option value="dipinjam">Dipinjam</option>
                        <option value="perbaikan">Perbaikan</option>
                    </select>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                <input type="file" name="foto" class="w-full px-4 py-2 border border-gray-300 rounded-lg" accept="image/*">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Simpan</button>
                <a href="/assets/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
