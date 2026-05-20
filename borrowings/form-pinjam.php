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
$users = $db->query("SELECT * FROM users ORDER BY nama");

$user_role = $_SESSION['role'] ?? '';
$user_id_login = $_SESSION['user_id'] ?? 0;

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
                <div class="flex gap-2">
                    <select name="asset_id" id="asset_id" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                        <option value="">Pilih Aset</option>
                        <?php while ($a = $assets->fetch_assoc()): ?>
                        <option value="<?= $a['id'] ?>" data-kode="<?= sanitize($a['kode_aset']) ?>" data-nama="<?= sanitize($a['nama_barang']) ?>"><?= sanitize($a['kode_aset'] . ' - ' . $a['nama_barang']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="openAssetModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm whitespace-nowrap">
                        <i class="fas fa-search mr-1"></i>Cari Aset
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Peminjam</label>
                <?php if ($user_role === 'user'): ?>
                    <input type="hidden" name="user_id" value="<?= $user_id_login ?>">
                    <p class="px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700"><?= sanitize($_SESSION['nama'] ?? '') ?></p>
                <?php else: ?>
                <select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Pilih Peminjam</option>
                    <?php while ($u = $users->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= sanitize($u['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" value="<?= date('Y-m-d') ?>" required onchange="setRencanaMin()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rencana Kembali</label>
                    <input type="date" name="rencana_kembali" id="rencana_kembali" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
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

<div id="assetModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeAssetModal()"></div>
    <div class="bg-white rounded-xl shadow-2xl p-6 z-10 max-w-2xl w-full mx-4 relative transform transition-all">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-lg font-bold text-gray-800">Cari Aset Tersedia</h4>
            <button type="button" onclick="closeAssetModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-4">
            <input type="text" id="assetSearchInput" placeholder="Cari aset..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" onkeyup="filterAssetTable()">
        </div>
        <div class="overflow-y-auto max-h-80">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="py-2 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="py-2 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Aset</th>
                        <th class="py-2 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Merek</th>
                        <th class="py-2 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="assetTableBody">
                    <?php
                    $allAssets = $db->query("SELECT * FROM assets WHERE status = 'tersedia' ORDER BY nama_barang");
                    while ($a = $allAssets->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-gray-50 cursor-pointer asset-row" data-id="<?= $a['id'] ?>" data-kode="<?= sanitize($a['kode_aset']) ?>" data-nama="<?= sanitize($a['nama_barang']) ?>" onclick="selectAsset(this)">
                        <td class="py-2 px-4 text-sm text-gray-900"><?= sanitize($a['kode_aset']) ?></td>
                        <td class="py-2 px-4 text-sm text-gray-900"><?= sanitize($a['nama_barang']) ?></td>
                        <td class="py-2 px-4 text-sm text-gray-900"><?= sanitize($a['merek'] ?? '-') ?></td>
                        <td class="py-2 px-4 text-sm">
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs">Pilih</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openAssetModal() {
    document.getElementById('assetModal').classList.remove('hidden');
    document.getElementById('assetModal').classList.add('flex');
    document.getElementById('assetSearchInput').value = '';
    filterAssetTable();
}

function closeAssetModal() {
    document.getElementById('assetModal').classList.add('hidden');
    document.getElementById('assetModal').classList.remove('flex');
}

function selectAsset(row) {
    var id = row.getAttribute('data-id');
    var kode = row.getAttribute('data-kode');
    var nama = row.getAttribute('data-nama');
    var select = document.getElementById('asset_id');
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === id) {
            select.selectedIndex = i;
            break;
        }
    }
    closeAssetModal();
}

function filterAssetTable() {
    var input = document.getElementById('assetSearchInput').value.toLowerCase();
    var rows = document.querySelectorAll('.asset-row');
    rows.forEach(function(row) {
        var kode = row.getAttribute('data-kode').toLowerCase();
        var nama = row.getAttribute('data-nama').toLowerCase();
        if (kode.indexOf(input) > -1 || nama.indexOf(input) > -1) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function setRencanaMin() {
    var tglPinjam = document.getElementById('tanggal_pinjam').value;
    document.getElementById('rencana_kembali').setAttribute('min', tglPinjam);
}

document.addEventListener('DOMContentLoaded', function() {
    setRencanaMin();
    document.getElementById('tanggal_pinjam').addEventListener('change', setRencanaMin);
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAssetModal();
});
</script>
<?php
$content = ob_get_clean();
include '../views/layout.php';
