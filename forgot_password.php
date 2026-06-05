<?php
require_once __DIR__ . '/config/init_sekolah.php';
require_once __DIR__ . '/core/App.php';

if (isset($_SESSION['user_id'])) {
    App::redirect('/');
}

$title = 'Lupa Password';
$token_display = $_SESSION['reset_link'] ?? null;
unset($_SESSION['reset_link']);

ob_start();
?>
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 py-12 px-4">
    <div class="w-full max-w-md">
        <?php if ($token_display): ?>
        <!-- Success: Link Reset -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Link Reset Password</h2>
                <p class="text-gray-600 mt-1">Gunakan link di bawah untuk mereset password</p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">Link bersifat rahasia!</p>
                        <p class="text-xs text-yellow-700 mt-1">Jangan bagikan link ini ke siapa pun. Link berlaku selama 1 jam.</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <label class="block text-xs font-medium text-gray-600 mb-1">Link Reset Password:</label>
                <div class="flex gap-2">
                    <input type="text" id="resetLink" value="<?= sanitize($token_display) ?>" class="input input-bordered flex-1 text-sm" readonly onclick="this.select()">
                    <button onclick="copyLink()" class="btn btn-primary btn-sm whitespace-nowrap">
                        <i class="fas fa-copy mr-1"></i>Salin
                    </button>
                </div>
            </div>

            <div class="text-center">
                <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Login
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Form: Request Reset -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-white text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Lupa Password</h2>
                <p class="text-gray-600 mt-1">Masukkan nama pengguna untuk mereset password</p>
            </div>

            <form method="POST" action="proses_forgot_password.php">
                <?= App::csrfField() ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pengguna</label>
                    <input type="text" name="nama" class="input input-bordered w-full" placeholder="Masukkan nama pengguna" required autofocus>
                </div>
                <p class="text-xs text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-1"></i>Link reset akan ditampilkan di layar setelah permintaan berhasil.
                </p>
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-paper-plane mr-1"></i>Kirim Permintaan Reset
                </button>
            </form>

            <div class="text-center mt-6">
                <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Login
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyLink() {
    var input = document.getElementById('resetLink');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = event.currentTarget;
        var icon = btn.querySelector('i');
        icon.className = 'fas fa-check mr-1';
        btn.classList.add('bg-green-600');
        setTimeout(function() {
            icon.className = 'fas fa-copy mr-1';
            btn.classList.remove('bg-green-600');
        }, 2000);
    });
}
</script>
<?php
$content = ob_get_clean();
include 'views/layout.php';
