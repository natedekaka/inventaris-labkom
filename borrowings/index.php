<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Daftar Peminjaman';
$db = db();

if (isset($_GET['approve'])) {
    App::requireRole(['admin', 'lab_assistant']);
    $id = (int)$_GET['approve'];
    $stmt = $db->prepare("UPDATE borrowings SET status = 'dipinjam', tanggal_pinjam = CURDATE() WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        // Get asset_id for logging
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
        App::setFlash('Peminjaman disetujui', 'success');
    } else {
        App::setFlash('Gagal menyetujui', 'danger');
    }
    App::redirect('/borrowings/');
}

if (isset($_GET['reject'])) {
    App::requireRole(['admin', 'lab_assistant']);
    $id = (int)$_GET['reject'];
    $stmt = $db->prepare("UPDATE borrowings SET status = 'rejected' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        // Get asset_id for logging
        $asset_stmt = $db->prepare("SELECT asset_id FROM borrowings WHERE id = ?");
        $asset_stmt->bind_param('i', $id);
        $asset_stmt->execute();
        $borrowing = $asset_stmt->get_result()->fetch_assoc();
        if ($borrowing) {
            logAssetAction($borrowing['asset_id'], $_SESSION['user_id'], 'borrow_rejected');
        }
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

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-800">Daftar Peminjaman</h3>
        <a href="form-pinjam.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Pinjam Aset</a>
    </div>

    <form method="GET" class="mb-4">
        <div class="flex gap-2">
            <input type="text" name="search" placeholder="Cari aset atau peminjam..." class="flex-1 px-4 py-2 border rounded-lg" value="<?= sanitize($search) ?>">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Cari</button>
        </div>
    </form>

    <!-- Filter Tabs -->
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
                <a href="?filter=<?= $key ?>&search=<?= urlencode($search) ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                          <?= $is_active 
                              ? "bg-{$color}-100 text-{$color}-800" 
                              : "bg-gray-100 text-gray-600 hover:bg-gray-200" ?>">
                    <?= $f['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Aset</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Peminjam</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pinjam</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Rencana Kembali</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-900">
                            <div>
                                <div class="font-medium"><?= sanitize($row['nama_aset']) ?></div>
                                <div class="text-xs text-gray-500"><?= sanitize($row['kode_aset']) ?></div>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['nama_peminjam']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($row['tanggal_pinjam']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($row['rencana_kembali']) ?></td>
                        <td class="py-4 px-6">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-blue-100 text-blue-800',
                                'dipinjam' => 'bg-green-100 text-green-800',
                                'dikembalikan' => 'bg-gray-100 text-gray-800',
                                'rejected' => 'bg-red-100 text-red-800'
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$row['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <?php if ($row['status'] === 'pending' && App::isAdmin() || App::isLabAssistant()): ?>
                                <a href="?approve=<?= $row['id'] ?>" 
                                   class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm inline-block mr-1"
                                   onclick="return confirm('Setujui peminjaman ini?')">Setujui</a>
                                <a href="?reject=<?= $row['id'] ?>" 
                                   class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm inline-block"
                                   onclick="return confirm('Tolak peminjaman ini?')">Tolak</a>
                            <?php elseif ($row['status'] === 'dipinjam'): ?>
                                <a href="pengembalian.php?id=<?= $row['id'] ?>" 
                                   class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm inline-block">Kembalikan</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="py-8 px-6 text-center text-gray-500">Tidak ada data peminjaman</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex gap-2 justify-center mb-8">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 rounded-lg border <?= $i === $page ? 'bg-blue-50 text-blue-600 font-semibold border-blue-300' : 'border-gray-300 hover:bg-gray-50' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
