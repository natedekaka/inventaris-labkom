<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant', 'guru']);

$title = 'Form Peminjaman';
$db = db();

$assets = $db->query("SELECT * FROM assets WHERE status = 'tersedia' ORDER BY nama_barang");
$users = $db->query("SELECT * FROM users ORDER BY nama");  // users table has 'nama' column - CORRECT

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/borrowings/form-pinjam.php');
    }
    $asset_id = (int)($_POST['asset_id'] ?? 0);
    $user_id = (int)($_POST['user_id'] ?? 0);
    $tanggal_pinjam = $_POST['tanggal_pinjam'] ?? date('Y-m-d');
    $rencana_kembali = $_POST['rencana_kembali'] ?? '';
    $keperluan = $_POST['keperluan'] ?? '';

    if (empty($rencana_kembali) || empty($keperluan)) {
        App::setFlash('Semua field harus diisi', 'danger');
    } elseif (strtotime($rencana_kembali) < strtotime($tanggal_pinjam)) {
        App::setFlash('Rencana kembali harus setelah tanggal pinjam', 'danger');
    } else {
        $check_asset = $db->prepare("SELECT id FROM assets WHERE id = ? AND status = 'tersedia'");
        $check_asset->bind_param('i', $asset_id);
        $check_asset->execute();
        $asset_result = $check_asset->get_result();

        $check_user = $db->prepare("SELECT id FROM users WHERE id = ?");
        $check_user->bind_param('i', $user_id);
        $check_user->execute();
        $user_result = $check_user->get_result();

        if ($asset_result->num_rows === 0) {
            App::setFlash('Aset tidak tersedia', 'danger');
        } elseif ($user_result->num_rows === 0) {
            App::setFlash('User tidak valid', 'danger');
        } else {
            $stmt = $db->prepare("INSERT INTO borrowings (user_id, asset_id, tanggal_pinjam, rencana_kembali, keperluan, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('iisss', $user_id, $asset_id, $tanggal_pinjam, $rencana_kembali, $keperluan);

            if ($stmt->execute()) {
                $borrowing_id = $stmt->insert_id;
                logAssetAction($asset_id, $_SESSION['user_id'], 'borrow_requested', ['borrowing_id' => $borrowing_id]);
                
                App::setFlash('Permintaan peminjaman berhasil dikirim, menunggu persetujuan', 'success');
                App::redirect('/borrowings/');
            } else {
                App::setFlash('Gagal mengirim permintaan', 'danger');
            }
        }
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Form Peminjaman Aset</h3>
        <form method="POST">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Aset</label>
                <select name="asset_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Pilih Aset</option>
                    <?php while ($a = $assets->fetch_assoc()): ?>
                    <option value="<?= $a['id'] ?>"><?= sanitize($a['kode_aset'] . ' - ' . $a['nama_barang']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Peminjam</label>
                <select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Pilih Peminjam</option>
                    <?php while ($u = $users->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= sanitize($u['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rencana Kembali</label>
                    <input type="date" name="rencana_kembali" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Keperluan</label>
                <textarea name="keperluan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" rows="3" required></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Pinjam</button>
                <a href="/borrowings/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
