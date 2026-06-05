<?php
require_once __DIR__ . '/config/init_sekolah.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/App.php';

if (isset($_SESSION['user_id'])) {
    App::redirect('/');
}

$token = $_GET['token'] ?? '';
$error = '';
$valid = false;
$nama_user = '';

if (empty($token)) {
    $error = 'Token tidak ditemukan. Silakan lupa password lagi.';
} else {
    $db = db();

    $stmt = $db->prepare("SELECT id, nama FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $valid = true;
        $nama_user = $user['nama'];
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Token sudah kedaluwarsa. Silakan lupa password lagi.';
        } else {
            $error = 'Token tidak valid. Silakan lupa password lagi.';
        }
    }
}

$title = 'Reset Password';

ob_start();
?>
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 py-12 px-4">
    <div class="w-full max-w-md">
        <?php if ($valid): ?>
        <!-- Form Reset Password -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-white text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Reset Password</h2>
                <p class="text-gray-600 mt-1">Buat password baru untuk <strong><?= sanitize($nama_user) ?></strong></p>
            </div>

            <form method="POST" action="proses_reset_password.php" onsubmit="return validatePassword()">
                <?= App::csrfField() ?>
                <input type="hidden" name="token" value="<?= sanitize($token) ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" class="input input-bordered w-full pr-10" required minlength="6">
                        <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p id="passwordInfo" class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <input type="password" name="password_confirm" id="password_confirm" class="input input-bordered w-full pr-10" required minlength="6">
                        <button type="button" onclick="togglePassword('password_confirm', this)" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p id="confirmInfo" class="text-xs text-gray-500 mt-1">Ulangi password yang sama</p>
                </div>

                <div id="errorMessage" class="hidden mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm"></div>

                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-save mr-1"></i>Simpan Password Baru
                </button>
            </form>

            <div class="text-center mt-6">
                <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Login
                </a>
            </div>
        </div>

        <script>
        function togglePassword(id, btn) {
            var input = document.getElementById(id);
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function validatePassword() {
            var pw = document.getElementById('password').value;
            var confirm = document.getElementById('password_confirm').value;
            var error = document.getElementById('errorMessage');

            if (pw.length < 6) {
                error.textContent = 'Password minimal 6 karakter';
                error.classList.remove('hidden');
                return false;
            }

            if (pw !== confirm) {
                error.textContent = 'Konfirmasi password tidak cocok';
                error.classList.remove('hidden');
                return false;
            }

            return true;
        }

        // Live confirm match
        document.getElementById('password_confirm').addEventListener('input', function() {
            var pw = document.getElementById('password').value;
            var info = document.getElementById('confirmInfo');
            if (this.value.length === 0) {
                info.className = 'text-xs text-gray-500 mt-1';
                info.textContent = 'Ulangi password yang sama';
            } else if (this.value === pw) {
                info.className = 'text-xs text-green-600 mt-1';
                info.textContent = '\u2713 Password cocok';
            } else {
                info.className = 'text-xs text-red-600 mt-1';
                info.textContent = '\u2717 Password tidak cocok';
            }
        });
        </script>

        <?php else: ?>
        <!-- Error -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-circle text-red-600 text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Gagal</h2>
                <p class="text-gray-600 mt-1"><?= sanitize($error) ?></p>
            </div>
            <div class="text-center">
                <a href="forgot_password.php" class="btn btn-primary">
                    <i class="fas fa-redo mr-1"></i>Minta Link Baru
                </a>
            </div>
            <div class="text-center mt-4">
                <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Login
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'views/layout.php';
