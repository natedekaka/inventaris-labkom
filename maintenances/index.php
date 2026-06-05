<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Daftar Maintenance';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/maintenances/');
    }
    $hapus_id = (int)$_POST['delete_id'];
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

                logActivity($_SESSION['user_id'], $_SESSION['nama'], 'delete', 'maintenances', $hapus_id, 'Menghapus maintenance ID: ' . $hapus_id);
                App::setFlash('Maintenance berhasil dihapus', 'success');
        } else {
            App::setFlash('Gagal menghapus maintenance', 'danger');
        }
    }
    App::redirect('/maintenances/');
}

$result = $db->query("SELECT m.*, a.nama_barang as nama_aset FROM maintenances m JOIN assets a ON m.asset_id = a.id ORDER BY m.id DESC");

$maintenances_list = [];
while ($row = $result->fetch_assoc()) {
    $maintenances_list[] = $row;
}

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-bold text-gray-800">Daftar Maintenance</h3>
    <a href="tambah.php" class="btn btn-primary">Tambah Maintenance</a>
</div>

<div class="hidden md:block">
    <div class="card bg-white shadow-md mb-8">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Aset</th>
                        <th>Tanggal Maintenance</th>
                        <th>Jenis</th>
                        <th>Teknisi</th>
                        <th>Biaya</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($maintenances_list) === 0): ?>
                    <tr>
                        <td colspan="6">
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="fas fa-tools text-5xl mb-4"></i>
                                <p class="text-lg font-medium text-gray-500">Belum ada maintenance</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($maintenances_list as $row): ?>
                    <tr>
                        <td><?= sanitize($row['nama_aset']) ?></td>
                        <td><?= formatTanggal($row['tanggal_maintenance']) ?></td>
                        <td><?= sanitize($row['jenis']) ?></td>
                        <td><?= sanitize($row['teknisi']) ?></td>
                        <td>Rp <?= number_format($row['biaya'], 0, ',', '.') ?></td>
                        <td>
                            <span class="<?= $row['status'] === 'selesai' ? 'badge badge-success' : 'badge badge-warning' ?>">
                                <?= sanitize($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <button type="button" data-delete-name="<?= htmlspecialchars($row['nama_aset'], ENT_QUOTES) ?>" onclick="openDeleteModal('index.php', <?= $row['id'] ?>, this.getAttribute('data-delete-name'))" class="btn btn-error btn-sm">Hapus</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="block md:hidden space-y-3 mb-6">
    <?php if (count($maintenances_list) === 0): ?>
    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
        <i class="fas fa-tools text-5xl mb-4"></i>
        <p class="text-lg font-medium text-gray-500">Belum ada maintenance</p>
    </div>
    <?php else: ?>
    <?php foreach ($maintenances_list as $row): ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
        <div class="flex justify-between items-start mb-2">
            <p class="font-semibold text-gray-900 dark:text-white"><?= sanitize($row['nama_aset']) ?></p>
            <span class="<?= $row['status'] === 'selesai' ? 'badge badge-success' : 'badge badge-warning' ?>">
                <?= sanitize($row['status']) ?>
            </span>
        </div>
        <div class="grid grid-cols-2 gap-2 text-sm mb-3">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Tanggal</span>
                <p class="text-gray-900 dark:text-white"><?= formatTanggal($row['tanggal_maintenance']) ?></p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Jenis</span>
                <p class="text-gray-900 dark:text-white"><?= sanitize($row['jenis']) ?></p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Teknisi</span>
                <p class="text-gray-900 dark:text-white"><?= sanitize($row['teknisi']) ?></p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Biaya</span>
                <p class="text-gray-900 dark:text-white">Rp <?= number_format($row['biaya'], 0, ',', '.') ?></p>
            </div>
        </div>
        <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm flex-1">Edit</a>
            <button type="button" data-delete-name="<?= htmlspecialchars($row['nama_aset'], ENT_QUOTES) ?>" onclick="openDeleteModal('index.php', <?= $row['id'] ?>, this.getAttribute('data-delete-name'))" class="btn btn-error btn-sm flex-1">Hapus</button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
