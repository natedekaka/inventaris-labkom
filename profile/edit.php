<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Edit Profil';
$db = db();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$nama = $user['nama'];
$nis = $user['nis'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/profile/edit.php');
    }

    $nama = $_POST['nama'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    if (empty($nama)) {
        $errors[] = 'Nama harus diisi';
    }

    $changePassword = !empty($new_password) || !empty($confirm_password);

    if ($changePassword) {
        if (empty($current_password)) {
            $errors[] = 'Password saat ini harus diisi untuk mengganti password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Password saat ini tidak sesuai';
        }

        if (strlen($new_password) > 0 && strlen($new_password) < 6) {
            $errors[] = 'Password baru minimal 6 karakter';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'Konfirmasi password tidak cocok';
        }
    }

    if (empty($errors)) {
        if ($changePassword && !empty($new_password)) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET nama = ?, password = ? WHERE id = ?");
            $updateStmt->bind_param('ssi', $nama, $hashedPassword, $_SESSION['user_id']);
        } else {
            $updateStmt = $db->prepare("UPDATE users SET nama = ? WHERE id = ?");
            $updateStmt->bind_param('si', $nama, $_SESSION['user_id']);
        }

        if ($updateStmt->execute()) {
            $_SESSION['nama'] = $nama;
            App::setFlash('Profil berhasil diperbarui', 'success');
            App::redirect('/profile/');
        } else {
            App::setFlash('Gagal memperbarui profil', 'danger');
        }
    } else {
        foreach ($errors as $error) {
            App::setFlash($error, 'danger');
        }
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="card bg-white shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Edit Profil</h3>
        <form method="POST">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="nama" class="input input-bordered w-full" value="<?= sanitize($nama) ?>" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">NIS</label>
                <input type="text" name="nis" class="input input-bordered w-full bg-gray-100" value="<?= sanitize($nis) ?>" readonly>
            </div>
            <hr class="my-6">
            <h4 class="text-md font-semibold text-gray-700 mb-4">Ganti Password</h4>
            <p class="text-sm text-gray-500 mb-4">Kosongkan jika tidak ingin mengubah password</p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                <input type="password" name="current_password" class="input input-bordered w-full" placeholder="Masukkan password saat ini">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" name="new_password" class="input input-bordered w-full" placeholder="Password baru">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="input input-bordered w-full" placeholder="Ulangi password baru">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="/profile/" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
