<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Daftar Peminjaman';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['approve'])) {
    App::requireRole(['admin', 'lab_assistant']);
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/borrowings/');
    }
    $id = (int)$_GET['approve'];
    $stmt = $db->prepare("UPDATE borrowings SET status = 'dipinjam', tanggal_pinjam = CURDATE() WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $asset_stmt = $db->prepare("SELECT asset_id FROM borrowings WHERE id = ?");
        $asset_stmt->bind_param('i', $id);
        $asset_stmt->execute();
        $borrowing = $asset_stmt->get_result()->fetch_assoc();
        if ($borrowing) {
            logAssetAction($borrowing['asset_id'], $_SESSION['user_id'], 'borrow_approved');
            $update_asset = $db->prepare("UPDATE assets SET status = 'dipinjam' WHERE id = ?");
            $update_asset->bind_param('i', $borrowing['asset_id']);
            $update_asset->execute();
        }
        logActivity($_SESSION['user_id'], $_SESSION['nama'], 'approve', 'borrowings', $id, 'Menyetujui peminjaman ID: ' . $id);
        App::setFlash('Peminjaman disetujui', 'success');
    } else {
        App::setFlash('Gagal menyetujui', 'danger');
    }
    App::redirect('/borrowings/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['reject'])) {
    App::requireRole(['admin', 'lab_assistant']);
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/borrowings/');
    }
    $id = (int)$_GET['reject'];
    $stmt = $db->prepare("UPDATE borrowings SET status = 'rejected' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $asset_stmt = $db->prepare("SELECT asset_id FROM borrowings WHERE id = ?");
        $asset_stmt->bind_param('i', $id);
        $asset_stmt->execute();
        $borrowing = $asset_stmt->get_result()->fetch_assoc();
        if ($borrowing) {
            logAssetAction($borrowing['asset_id'], $_SESSION['user_id'], 'borrow_rejected');
        }
        logActivity($_SESSION['user_id'], $_SESSION['nama'], 'reject', 'borrowings', $id, 'Menolak peminjaman ID: ' . $id);
        App::setFlash('Peminjaman ditolak', 'success');
    } else {
        App::setFlash('Gagal menolak', 'danger');
    }
    App::redirect('/borrowings/');
}

$status_filter = $_GET['filter'] ?? 'all';
$where = '';
$params = [];
if ($status_filter !== 'all') {
    $where = "WHERE b.status = ?";
    $params = [$status_filter];
}

$search = $_GET['search'] ?? '';
$dari_tanggal = $_GET['dari_tanggal'] ?? '';
$sampai_tanggal = $_GET['sampai_tanggal'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

if ($search) {
    $searchClause = "(a.nama_barang LIKE ? OR a.kode_aset LIKE ? OR u.nama LIKE ?)";
    if ($where === '') {
        $where = "WHERE $searchClause";
    } else {
        $where .= " AND $searchClause";
    }
    array_push($params, "%$search%", "%$search%", "%$search%");
}

if ($dari_tanggal && $sampai_tanggal) {
    $dateClause = "b.tanggal_pinjam BETWEEN ? AND ?";
    if ($where === '') {
        $where = "WHERE $dateClause";
    } else {
        $where .= " AND $dateClause";
    }
    array_push($params, $dari_tanggal, $sampai_tanggal);
} elseif ($dari_tanggal) {
    $dateClause = "b.tanggal_pinjam >= ?";
    if ($where === '') {
        $where = "WHERE $dateClause";
    } else {
        $where .= " AND $dateClause";
    }
    array_push($params, $dari_tanggal);
} elseif ($sampai_tanggal) {
    $dateClause = "b.tanggal_pinjam <= ?";
    if ($where === '') {
        $where = "WHERE $dateClause";
    } else {
        $where .= " AND $dateClause";
    }
    array_push($params, $sampai_tanggal);
}

$countSql = "SELECT COUNT(*) as total FROM borrowings b 
        JOIN assets a ON b.asset_id = a.id 
        JOIN users u ON b.user_id = u.id 
        $where";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countTypes = str_repeat('s', count($params));
    $countStmt->bind_param($countTypes, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

$sql = "SELECT b.*, a.nama_barang as nama_aset, a.kode_aset, u.nama as nama_peminjam 
        FROM borrowings b 
        JOIN assets a ON b.asset_id = a.id 
        JOIN users u ON b.user_id = u.id 
        $where 
        ORDER BY b.id DESC 
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$paramCount = count($params);
$types = str_repeat('s', $paramCount) . 'ii';
$bindParams = array_merge($params, [$limit, $offset]);
$stmt->bind_param($types, ...$bindParams);
$stmt->execute();
$result = $stmt->get_result();

$borrowings_list = [];
while ($row = $result->fetch_assoc()) {
    $borrowings_list[] = $row;
}

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-800">Daftar Peminjaman</h3>
        <a href="form-pinjam.php" class="btn btn-primary">Pinjam Aset</a>
    </div>

    <form method="GET" class="mb-4">
        <div class="flex gap-2 mb-2">
            <input type="text" name="search" placeholder="Cari aset atau peminjam..." class="input input-bordered flex-1" value="<?= sanitize($search) ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
        </div>
        <div class="flex gap-2 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari Tanggal</label>
                <input type="date" name="dari_tanggal" class="input input-bordered input-sm" value="<?= sanitize($dari_tanggal) ?>">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai_tanggal" class="input input-bordered input-sm" value="<?= sanitize($sampai_tanggal) ?>">
            </div>
            <button type="submit" class="btn btn-ghost btn-sm">Filter Tanggal</button>
            <?php if ($dari_tanggal || $sampai_tanggal): ?>
            <a href="?filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
        <div class="flex gap-2 flex-wrap">
            <?php
            $filters = [
                'all' => ['label' => 'Semua', 'color' => 'gray'],
                'pending' => ['label' => 'Pending', 'color' => 'yellow'],
                'approved' => ['label' => 'Disetujui', 'color' => 'blue'],
                'dipinjam' => ['label' => 'Dipinjam', 'color' => 'green'],
                'dikembalikan' => ['label' => 'Dikembalikan', 'color' => 'gray'],
                'rejected' => ['label' => 'Ditolak', 'color' => 'red']
            ];
            foreach ($filters as $key => $f):
                $is_active = $status_filter === $key;
                $color = $f['color'];
            ?>
                <a href="?filter=<?= $key ?>&search=<?= urlencode($search) ?>&dari_tanggal=<?= urlencode($dari_tanggal) ?>&sampai_tanggal=<?= urlencode($sampai_tanggal) ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                          <?= $is_active 
                              ? "bg-{$color}-100 text-{$color}-800" 
                              : "bg-gray-100 text-gray-600 hover:bg-gray-200" ?>">
                    <?= $f['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="hidden md:block">
        <div class="card bg-white shadow-md mb-8">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Aset</th>
                            <th>Peminjam</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowings_list as $row): ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="font-medium"><?= sanitize($row['nama_aset']) ?></div>
                                    <div class="text-xs text-gray-500"><?= sanitize($row['kode_aset']) ?></div>
                                </div>
                            </td>
                            <td><?= sanitize($row['nama_peminjam']) ?></td>
                            <td><?= formatTanggal($row['tanggal_pinjam']) ?></td>
                            <td><?= formatTanggal($row['rencana_kembali']) ?></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'badge badge-warning',
                                    'approved' => 'badge badge-info',
                                    'dipinjam' => 'badge badge-success',
                                    'dikembalikan' => 'badge badge-ghost',
                                    'rejected' => 'badge badge-error'
                                ];
                                ?>
                                <span class="<?= $statusColors[$row['status']] ?? 'badge badge-ghost' ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'pending' && (App::isAdmin() || App::isLabAssistant())): ?>
                                    <button onclick="openApproveModal(<?= $row['id'] ?>, '<?= sanitize($row['nama_peminjam']) ?>')"
                                       class="btn btn-success btn-sm mr-1">Setujui</button>
                                    <button onclick="openRejectModal(<?= $row['id'] ?>, '<?= sanitize($row['nama_peminjam']) ?>')"
                                       class="btn btn-error btn-sm">Tolak</button>
                                <?php elseif ($row['status'] === 'dipinjam'): ?>
                                    <a href="pengembalian.php?id=<?= $row['id'] ?>" 
                                       class="btn btn-success btn-sm">Kembalikan</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($borrowings_list) === 0): ?>
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                    <i class="fas fa-handshake text-5xl mb-4"></i>
                                    <p class="text-lg font-medium text-gray-500">Belum ada peminjaman</p>
                                    <p class="text-sm text-gray-400 mt-1">Belum ada aktivitas peminjaman aset</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (count($borrowings_list) === 0): ?>
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <i class="fas fa-handshake text-5xl mb-4"></i>
            <p class="text-lg font-medium text-gray-500">Belum ada peminjaman</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="block md:hidden space-y-3 mb-6">
        <?php foreach ($borrowings_list as $row): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white"><?= sanitize($row['nama_aset']) ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= sanitize($row['kode_aset']) ?></p>
                </div>
                <?php
                $statusColors = [
                    'pending' => 'badge badge-warning',
                    'approved' => 'badge badge-info',
                    'dipinjam' => 'badge badge-success',
                    'dikembalikan' => 'badge badge-ghost',
                    'rejected' => 'badge badge-error'
                ];
                ?>
                <span class="<?= $statusColors[$row['status']] ?? 'badge badge-ghost' ?>">
                    <?= ucfirst($row['status']) ?>
                </span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Peminjam</span>
                    <p class="text-gray-900 dark:text-white"><?= sanitize($row['nama_peminjam']) ?></p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Tgl Pinjam</span>
                    <p class="text-gray-900 dark:text-white"><?= formatTanggal($row['tanggal_pinjam']) ?></p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Rencana Kembali</span>
                    <p class="text-gray-900 dark:text-white"><?= formatTanggal($row['rencana_kembali']) ?></p>
                </div>
            </div>
            <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                <?php if ($row['status'] === 'pending' && (App::isAdmin() || App::isLabAssistant())): ?>
                    <button onclick="openApproveModal(<?= $row['id'] ?>, '<?= sanitize($row['nama_peminjam']) ?>')" class="btn btn-success btn-sm flex-1">Setujui</button>
                    <button onclick="openRejectModal(<?= $row['id'] ?>, '<?= sanitize($row['nama_peminjam']) ?>')" class="btn btn-error btn-sm flex-1">Tolak</button>
                <?php elseif ($row['status'] === 'dipinjam'): ?>
                    <a href="pengembalian.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm flex-1">Kembalikan</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (count($borrowings_list) === 0): ?>
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <i class="fas fa-handshake text-5xl mb-4"></i>
            <p class="text-lg font-medium text-gray-500">Belum ada peminjaman</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="join mb-8">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&dari_tanggal=<?= urlencode($dari_tanggal) ?>&sampai_tanggal=<?= urlencode($sampai_tanggal) ?>" class="join-item btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<div id="approveRejectModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeActionModal()"></div>
    <div class="bg-white rounded-xl shadow-2xl p-6 z-10 max-w-md w-full mx-4 relative transform transition-all">
        <div class="text-center mb-4">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" id="actionModalIcon">
                <i class="fas fa-check text-2xl text-green-600"></i>
            </div>
            <h4 class="text-xl font-bold text-gray-800 mb-2" id="actionModalTitle">Konfirmasi</h4>
            <p class="text-gray-600" id="actionModalMessage">Apakah Anda yakin?</p>
        </div>
        <form method="POST" id="actionModalForm">
            <?= App::csrfField() ?>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-success flex-1" id="actionModalConfirmBtn">
                    <i class="fas fa-check mr-1"></i> Ya
                </button>
                <button type="button" onclick="closeActionModal()" class="btn btn-ghost flex-1">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApproveModal(id, nama) {
    document.getElementById('actionModalForm').action = '?approve=' + id;
    document.getElementById('actionModalTitle').textContent = 'Setujui Peminjaman';
    document.getElementById('actionModalMessage').textContent = 'Apakah Anda yakin ingin menyetujui peminjaman oleh "' + nama + '"?';
    document.getElementById('actionModalIcon').innerHTML = '<i class="fas fa-check text-2xl text-green-600"></i>';
    document.getElementById('actionModalConfirmBtn').className = 'btn btn-success flex-1';
    document.getElementById('actionModalConfirmBtn').innerHTML = '<i class="fas fa-check mr-1"></i> Ya, Setujui';
    document.getElementById('approveRejectModal').classList.remove('hidden');
    document.getElementById('approveRejectModal').classList.add('flex');
}

function openRejectModal(id, nama) {
    document.getElementById('actionModalForm').action = '?reject=' + id;
    document.getElementById('actionModalTitle').textContent = 'Tolak Peminjaman';
    document.getElementById('actionModalMessage').textContent = 'Apakah Anda yakin ingin menolak peminjaman oleh "' + nama + '"?';
    document.getElementById('actionModalIcon').innerHTML = '<i class="fas fa-times text-2xl text-red-600"></i>';
    document.getElementById('actionModalConfirmBtn').className = 'btn btn-error flex-1';
    document.getElementById('actionModalConfirmBtn').innerHTML = '<i class="fas fa-times mr-1"></i> Ya, Tolak';
    document.getElementById('approveRejectModal').classList.remove('hidden');
    document.getElementById('approveRejectModal').classList.add('flex');
}

function closeActionModal() {
    document.getElementById('approveRejectModal').classList.add('hidden');
    document.getElementById('approveRejectModal').classList.remove('flex');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeActionModal();
});
</script>
<?php
$content = ob_get_clean();
include '../views/layout.php';
