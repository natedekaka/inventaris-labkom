<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireRole('admin');

$title = 'Manajemen User';
$db = db();

if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    if ($hapus_id == $_SESSION['user_id']) {
        App::setFlash('Tidak bisa menghapus akun sendiri', 'danger');
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $hapus_id);
        if ($stmt->execute()) {
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
        <a href="tambah.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Tambah User</a>
    </div>

    <div class="bg-white rounded-xl shadow-md mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">NIS</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-900"><?= $row['id'] ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['nama']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['nis'] ?? '-') ?></td>
                        <td class="py-4 px-6">
                            <?php
                            $roleColors = [
                                'admin' => 'bg-purple-100 text-purple-800',
                                'lab_assistant' => 'bg-blue-100 text-blue-800',
                                'guru' => 'bg-green-100 text-green-800',
                                'siswa' => 'bg-gray-100 text-gray-800'
                            ];
                            $color = $roleColors[$row['role']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $color ?>">
                                <?= ucfirst(str_replace('_', ' ', $row['role'])) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm inline-block">Edit</a>
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <a href="?hapus=<?= $row['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm inline-block" onclick="return confirm('Hapus user ini?')">Hapus</a>
                            <?php endif; ?>
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
