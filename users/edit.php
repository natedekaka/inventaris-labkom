<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireRole('admin');

$title = 'Edit User';
$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    App::setFlash('User tidak ditemukan', 'danger');
    App::redirect('/users/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/users/edit.php?id=' . $id);
    }
    $nama = $_POST['nama'] ?? '';
    $nis = $_POST['nis'] ?? null;
    $role = $_POST['role'] ?? 'siswa';
    $password = $_POST['password'] ?? '';

    if (empty($nama)) {
        App::setFlash('Nama harus diisi', 'danger');
    } else {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $db->prepare("UPDATE users SET nama = ?, nis = ?, role = ?, password = ? WHERE id = ?");
            $update_stmt->bind_param('ssssi', $nama, $nis, $role, $hashedPassword, $id);
        } else {
            $update_stmt = $db->prepare("UPDATE users SET nama = ?, nis = ?, role = ? WHERE id = ?");
            $update_stmt->bind_param('sssi', $nama, $nis, $role, $id);
        }

        if ($update_stmt->execute()) {
            App::setFlash('User berhasil diupdate', 'success');
            App::redirect('/users/');
        } else {
            App::setFlash('Gagal mengupdate user', 'danger');
        }
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Edit User - <?= sanitize($user['nama']) ?></h3>
        <form method="POST">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="nama" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= sanitize($user['nama']) ?>" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">NIS (Opsional)</label>
                <input type="text" name="nis" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= sanitize($user['nis'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User (Siswa)</option>
                    <option value="viewer" <?= $user['role'] === 'viewer' ? 'selected' : '' ?>>Viewer (Guru)</option>
                    <option value="lab_assistant" <?= $user['role'] === 'lab_assistant' ? 'selected' : '' ?>>Lab Assistant</option>
                    <option value="operator" <?= $user['role'] === 'operator' ? 'selected' : '' ?>>Operator</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Kosongkan untuk tetap gunakan password lama">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Update</button>
                <a href="/users/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
