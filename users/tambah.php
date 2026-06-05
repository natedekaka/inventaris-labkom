<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireRole('admin');

$title = 'Tambah User';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/users/tambah.php');
    }
    $nama = $_POST['nama'] ?? '';
    $nis = $_POST['nis'] ?? null;
    $role = $_POST['role'] ?? 'siswa';
    $password = $_POST['password'] ?? '';

    if (empty($nama) || empty($password)) {
        App::setFlash('Nama dan password harus diisi', 'danger');
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (nama, nis, role, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $nama, $nis, $role, $hashedPassword);

        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], $_SESSION['nama'], 'create', 'users', $db->insert_id, 'Menambahkan user: ' . $nama);
            App::setFlash('User berhasil ditambahkan', 'success');
            App::redirect('/users/');
        } else {
            App::setFlash('Gagal menambahkan user', 'danger');
        }
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="card bg-white shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Tambah User</h3>
        <form method="POST">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="nama" class="input input-bordered w-full" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">NIS (Opsional)</label>
                <input type="text" name="nis" class="input input-bordered w-full">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="select select-bordered w-full" required>
                    <option value="user">User (Siswa)</option>
                    <option value="viewer">Viewer (Guru)</option>
                    <option value="lab_assistant">Lab Assistant</option>
                    <option value="operator">Operator</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" class="input input-bordered w-full" required>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="/users/" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
