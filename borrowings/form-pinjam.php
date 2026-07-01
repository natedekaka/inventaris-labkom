<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant', 'operator']);

$title = 'Form Peminjaman';
$db = db();

$preselect_asset_id = (int)($_GET['asset_id'] ?? 0);
$assets = $db->query("SELECT * FROM assets WHERE status = 'tersedia' ORDER BY nama_barang");
$users = $db->query("SELECT * FROM users ORDER BY nama");

$user_role = $_SESSION['role'] ?? '';
$user_id_login = $_SESSION['user_id'] ?? 0;

$preselect_nama = '';
if ($preselect_asset_id) {
    $stmt = $db->prepare("SELECT nama_barang, kode_aset FROM assets WHERE id = ? AND status = 'tersedia'");
    $stmt->bind_param('i', $preselect_asset_id);
    $stmt->execute();
    $preselect = $stmt->get_result()->fetch_assoc();
    if ($preselect) {
        $preselect_nama = $preselect['kode_aset'] . ' - ' . $preselect['nama_barang'];
    } else {
        $preselect_asset_id = 0;
        App::setFlash('Aset tidak tersedia untuk dipinjam', 'warning');
    }
}

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

        $check_user = $db->prepare("SELECT id, nama FROM users WHERE id = ?");
        $check_user->bind_param('i', $user_id);
        $check_user->execute();
        $user_result = $check_user->get_result();

        if ($asset_result->num_rows === 0) {
            App::setFlash('Aset tidak tersedia', 'danger');
        } elseif ($user_result->num_rows === 0) {
            App::setFlash('User tidak valid', 'danger');
        } else {
            $user_data = $user_result->fetch_assoc();
            $peminjam_nama = $user_data['nama'];
            $stmt = $db->prepare("INSERT INTO borrowings (user_id, asset_id, tanggal_pinjam, rencana_kembali, keperluan, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('iisss', $user_id, $asset_id, $tanggal_pinjam, $rencana_kembali, $keperluan);

            if ($stmt->execute()) {
                $borrowing_id = $stmt->insert_id;
                logAssetAction($asset_id, $_SESSION['user_id'], 'borrow_requested', ['borrowing_id' => $borrowing_id]);
                logActivity($_SESSION['user_id'], $_SESSION['nama'], 'create', 'borrowings', $db->getLastId(), 'Peminjaman aset oleh ' . $peminjam_nama);
                
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
    <div class="card bg-white shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Form Peminjaman Aset</h3>
        <form method="POST">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Aset</label>
                <div class="flex gap-2">
                    <select name="asset_id" id="asset_id" class="select select-bordered flex-1" required>
                        <option value="">Pilih Aset</option>
                        <?php while ($a = $assets->fetch_assoc()): 
                            $selected = ($preselect_asset_id && $a['id'] == $preselect_asset_id) ? 'selected' : '';
                        ?>
                        <option value="<?= $a['id'] ?>" <?= $selected ?> data-kode="<?= sanitize($a['kode_aset']) ?>" data-nama="<?= sanitize($a['nama_barang']) ?>"><?= sanitize($a['kode_aset'] . ' - ' . $a['nama_barang']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($preselect_nama): ?>
                    <p class="text-sm text-green-600 mt-1"><i class="fas fa-check-circle mr-1"></i>Aset dipilih: <?= sanitize($preselect_nama) ?></p>
                    <?php endif; ?>
                    <button type="button" onclick="openAssetModal()" class="btn btn-primary btn-sm">
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
                <select name="user_id" class="select select-bordered w-full" required>
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
                    <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" class="input input-bordered w-full" value="<?= date('Y-m-d') ?>" required onchange="setRencanaMin()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rencana Kembali</label>
                    <input type="date" name="rencana_kembali" id="rencana_kembali" class="input input-bordered w-full" required>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Keperluan</label>
                <textarea name="keperluan" class="textarea textarea-bordered w-full" rows="3" required></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Pinjam</button>
                <a href="/borrowings/" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</div>

<dialog id="assetModal" class="modal">
    <div class="modal-box max-w-2xl">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-lg font-bold text-gray-800">Cari Aset Tersedia</h4>
            <button type="button" onclick="closeAssetModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-4">
            <input type="text" id="assetSearchInput" placeholder="Cari aset..." class="input input-bordered w-full" onkeyup="filterAssetTable()">
        </div>
        <div class="overflow-y-auto max-h-80">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Aset</th>
                        <th>Merek</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="assetTableBody">
                    <?php
                    $allAssets = $db->query("SELECT * FROM assets WHERE status = 'tersedia' ORDER BY nama_barang");
                    while ($a = $allAssets->fetch_assoc()):
                    ?>
                    <tr class="asset-row" data-id="<?= $a['id'] ?>" data-kode="<?= sanitize($a['kode_aset']) ?>" data-nama="<?= sanitize($a['nama_barang']) ?>" onclick="selectAsset(this)">
                        <td><?= sanitize($a['kode_aset']) ?></td>
                        <td><?= sanitize($a['nama_barang']) ?></td>
                        <td><?= sanitize($a['merek'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-primary btn-xs">Pilih</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<script>
function openAssetModal() {
    document.getElementById('assetModal').showModal();
    document.getElementById('assetSearchInput').value = '';
    filterAssetTable();
}

function closeAssetModal() {
    document.getElementById('assetModal').close();
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


</script>
<?php
$content = ob_get_clean();
include '../views/layout.php';
