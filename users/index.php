<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireRole('admin');

$title = 'Manajemen User';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/users/');
    }
    $hapus_id = (int)$_POST['delete_id'];
    if ($hapus_id == $_SESSION['user_id']) {
        App::setFlash('Tidak bisa menghapus akun sendiri', 'danger');
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $hapus_id);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], $_SESSION['nama'], 'delete', 'users', $hapus_id, 'Menghapus user ID: ' . $hapus_id);
            App::setFlash('User berhasil dihapus', 'success');
        } else {
            App::setFlash('Gagal menghapus user', 'danger');
        }
    }
    App::redirect('/users/');
}

$result = $db->query("SELECT * FROM users ORDER BY id DESC");

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-800">Manajemen User</h3>
        <a href="tambah.php" class="btn btn-primary">Tambah User</a>
    </div>

    <div class="card bg-white shadow-md mb-8">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>NIS</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="5">
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="fas fa-users text-5xl mb-4"></i>
                                <p class="text-lg font-medium text-gray-500">Belum ada pengguna</p>
                                <a href="tambah.php" class="btn btn-primary btn-sm mt-4"><i class="fas fa-plus mr-1"></i>Tambah User</a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= sanitize($row['nama']) ?></td>
                        <td><?= sanitize($row['nis'] ?? '-') ?></td>
                        <td>
                            <?php
                            $roleColors = [
                                'admin' => 'bg-purple-100 text-purple-800',
                                'user' => 'bg-gray-100 text-gray-800',
                                'viewer' => 'bg-green-100 text-green-800',
                                'lab_assistant' => 'bg-blue-100 text-blue-800',
                                'operator' => 'bg-yellow-100 text-yellow-800'
                            ];
                            $color = $roleColors[$row['role']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="badge <?= $color ?>">
                                <?= ucfirst(str_replace('_', ' ', $row['role'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <button type="button" data-delete-name="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>" onclick="openDeleteModal('index.php', <?= $row['id'] ?>, this.getAttribute('data-delete-name'))" class="btn btn-error btn-sm">Hapus</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
