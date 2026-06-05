<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole('admin');

$title = 'Activity Log';
$db = db();

// Filters
$filter_action = $_GET['action'] ?? '';
$filter_table = $_GET['table'] ?? '';
$dari_tanggal = $_GET['dari_tanggal'] ?? '';
$sampai_tanggal = $_GET['sampai_tanggal'] ?? '';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
$types = '';

if ($filter_action) {
    $where .= ($where ? " AND" : "WHERE") . " l.action = ?";
    $params[] = $filter_action;
    $types .= 's';
}
if ($filter_table) {
    $where .= ($where ? " AND" : "WHERE") . " l.table_name = ?";
    $params[] = $filter_table;
    $types .= 's';
}
if ($dari_tanggal) {
    $where .= ($where ? " AND" : "WHERE") . " DATE(l.created_at) >= ?";
    $params[] = $dari_tanggal;
    $types .= 's';
}
if ($sampai_tanggal) {
    $where .= ($where ? " AND" : "WHERE") . " DATE(l.created_at) <= ?";
    $params[] = $sampai_tanggal;
    $types .= 's';
}
if ($search) {
    $where .= ($where ? " AND" : "WHERE") . " (l.user_name LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM activity_logs l $where";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Get logs
$sql = "SELECT l.* FROM activity_logs l $where ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$bindParams = array_merge($params, [$limit, $offset]);
$bindTypes = $types . 'ii';
if (!empty($params)) {
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get distinct actions and tables for filter dropdowns
$actions = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$tables = $db->query("SELECT DISTINCT table_name FROM activity_logs ORDER BY table_name");

// Action badges
$actionBadges = [
    'create' => 'badge-success',
    'update' => 'badge-info',
    'delete' => 'badge-error',
    'approve' => 'badge-success',
    'reject' => 'badge-error',
    'return' => 'badge-warning',
    'login' => 'badge-ghost',
    'logout' => 'badge-ghost',
];

$actionIcons = [
    'create' => 'fa-plus-circle',
    'update' => 'fa-edit',
    'delete' => 'fa-trash',
    'approve' => 'fa-check-circle',
    'reject' => 'fa-times-circle',
    'return' => 'fa-undo',
    'login' => 'fa-sign-in-alt',
    'logout' => 'fa-sign-out-alt',
];

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h3 class="text-xl font-bold">Activity Log</h3>
        <div class="text-sm text-gray-500">
            Total: <?= number_format($total) ?> aktivitas
        </div>
    </div>

    <!-- Filter -->
    <div class="card bg-white shadow-md p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="text-xs font-medium text-gray-500 mb-1 block">Aksi</label>
                <select name="action" class="select select-bordered select-sm">
                    <option value="">Semua Aksi</option>
                    <?php while ($a = $actions->fetch_assoc()): ?>
                    <option value="<?= $a['action'] ?>" <?= $filter_action === $a['action'] ? 'selected' : '' ?>><?= $a['action'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 mb-1 block">Tabel</label>
                <select name="table" class="select select-bordered select-sm">
                    <option value="">Semua Tabel</option>
                    <?php while ($t = $tables->fetch_assoc()): ?>
                    <option value="<?= $t['table_name'] ?>" <?= $filter_table === $t['table_name'] ? 'selected' : '' ?>><?= $t['table_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 mb-1 block">Dari</label>
                <input type="date" name="dari_tanggal" class="input input-bordered input-sm" value="<?= $dari_tanggal ?>">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 mb-1 block">Sampai</label>
                <input type="date" name="sampai_tanggal" class="input input-bordered input-sm" value="<?= $sampai_tanggal ?>">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 mb-1 block">Cari</label>
                <input type="text" name="search" class="input input-bordered input-sm" placeholder="User atau deskripsi..." value="<?= sanitize($search) ?>">
            </div>
            <div class="flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="/activity/" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="hidden md:block">
        <div class="card bg-white shadow-md overflow-hidden">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Tabel</th>
                        <th>Deskripsi</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) === 0): ?>
                    <tr>
                        <td colspan="6">
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="fas fa-history text-5xl mb-4"></i>
                                <p class="text-lg font-medium text-gray-500">Belum ada aktivitas</p>
                                <p class="text-sm text-gray-400 mt-1">Data log akan muncul saat ada aktivitas</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="whitespace-nowrap text-sm"><?= formatTanggal($log['created_at']) ?><br><span class="text-xs text-gray-400"><?= date('H:i', strtotime($log['created_at'])) ?></span></td>
                        <td><?= sanitize($log['user_name']) ?></td>
                        <td>
                            <span class="badge <?= $actionBadges[$log['action']] ?? 'badge-ghost' ?> gap-1">
                                <i class="fas <?= $actionIcons[$log['action']] ?? 'fa-circle' ?> text-xs"></i>
                                <?= $log['action'] ?>
                            </span>
                        </td>
                        <td><span class="badge badge-ghost"><?= $log['table_name'] ?></span></td>
                        <td class="max-w-xs truncate" title="<?= sanitize($log['description']) ?>"><?= sanitize($log['description']) ?></td>
                        <td class="text-xs text-gray-400 font-mono"><?= $log['ip_address'] ?: '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Cards -->
    <div class="block md:hidden space-y-3">
        <?php if (count($logs) === 0): ?>
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <i class="fas fa-history text-5xl mb-4"></i>
            <p class="text-lg font-medium text-gray-500">Belum ada aktivitas</p>
        </div>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <div class="card bg-white shadow-md p-4">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="font-medium text-sm"><?= sanitize($log['user_name']) ?></p>
                    <p class="text-xs text-gray-400"><?= formatTanggal($log['created_at']) ?> <?= date('H:i', strtotime($log['created_at'])) ?></p>
                </div>
                <span class="badge <?= $actionBadges[$log['action']] ?? 'badge-ghost' ?> gap-1 text-xs">
                    <i class="fas <?= $actionIcons[$log['action']] ?? 'fa-circle' ?>"></i>
                    <?= $log['action'] ?>
                </span>
            </div>
            <p class="text-xs text-gray-600 mb-1">
                <span class="badge badge-ghost badge-xs"><?= $log['table_name'] ?></span>
                <?= sanitize($log['description']) ?>
            </p>
            <p class="text-xs text-gray-400 font-mono">IP: <?= $log['ip_address'] ?: '-' ?></p>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center mt-6">
        <div class="join">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&action=<?= urlencode($filter_action) ?>&table=<?= urlencode($filter_table) ?>&dari_tanggal=<?= urlencode($dari_tanggal) ?>&sampai_tanggal=<?= urlencode($sampai_tanggal) ?>&search=<?= urlencode($search) ?>"
               class="join-item btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
