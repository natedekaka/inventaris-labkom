<?php
require_once __DIR__ . '/config/init_sekolah.php';
require_once __DIR__ . '/core/App.php';

if (isset($_SESSION['user_id'])) {
    App::redirect('/');
}

$title = 'Login';
ob_start();
?>
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 py-12 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-desktop text-white text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Login Inventaris Lab</h2>
                <p class="text-gray-600 mt-1"><?= NAMA_SEKOLAH ?></p>
            </div>

            <form method="POST" action="proses_login.php">
                <?= App::csrfField() ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pengguna</label>
                    <input type="text" name="nama" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition duration-200 font-semibold">Login</button>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'views/layout.php';
