<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Daftar Maintenance';
$db = db();

if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    $stmt = $db->prepare("SELECT asset_id FROM maintenances WHERE id = ?");
    $stmt->bind_param('i', $hapus_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $maint = $result->fetch_assoc();

    if ($maint) {
        $delete_stmt = $db->prepare("DELETE FROM maintenances WHERE id = ?");
        $delete_stmt->bind_param('i', $hapus_id);
        if ($delete_stmt->execute()) {
            $check_stmt = $db->prepare("SELECT id FROM maintenances WHERE asset_id = ? AND status = 'belum'");
            $check_stmt->bind_param('i', $maint['asset_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                $update_asset = $db->prepare("UPDATE assets SET status = 'tersedia' WHERE id = ?");
                $update_asset->bind_param('i', $maint['asset_id']);
                $update_asset->execute();
            }

            App::setFlash('Maintenance berhasil dihapus', 'success');
        } else {
            App::setFlash('Gagal menghapus maintenance', 'danger');
        }
    }
    App::redirect('/maintenances/');
}

$result = $db->query("SELECT m.*, a.nama_barang as nama_aset FROM maintenances m JOIN assets a ON m.asset_id = a.id ORDER BY m.id DESC");

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-bold text-gray-800">Daftar Maintenance</h3>
    <a href="tambah.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Tambah Maintenance</a>
</div>

<div class="bg-white rounded-xl shadow-md mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Aset</th>
                    <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Maintenance</th>
                    <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Teknisi</th>
                    <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Biaya</th>
                    <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['nama_aset']) ?></td>
                    <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($row['tanggal_maintenance']) ?></td>
                    <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['jenis']) ?></td>
                    <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['teknisi']) ?></td>
                    <td class="py-4 px-6 text-sm text-gray-900">Rp <?= number_format($row['biaya'], 0, ',', '.') ?></td>
                    <td class="py-4 px-6">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $row['status'] === 'selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                            <?= sanitize($row['status']) ?>
                        </span>
                    </td>
                    <td class="py-4 px-6">
                        <a href="edit.php?id=<?= $row['id'] ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm inline-block">Edit</a>
                        <a href="index.php?hapus=<?= $row['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm inline-block" onclick="return confirm('Yakin hapus?')">Hapus</a>
                    </td>
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
