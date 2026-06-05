<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Daftar Aset';
$db = db();

$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
$lokasi_filter = $_GET['lokasi'] ?? '';

$where = '';
$params = [];
$types = '';

if ($search) {
    $where = "WHERE (a.nama_barang LIKE ? OR a.kode_aset LIKE ?)";
    $params = ["%$search%", "%$search%"];
    $types = 'ss';
}
if ($status_filter) {
    $where .= ($where ? " AND" : "WHERE") . " a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($kategori_filter) {
    $where .= ($where ? " AND" : "WHERE") . " a.category_id = ?";
    $params[] = (int)$kategori_filter;
    $types .= 'i';
}
if ($lokasi_filter) {
    $where .= ($where ? " AND" : "WHERE") . " a.location_id = ?";
    $params[] = (int)$lokasi_filter;
    $types .= 'i';
}

$sql = "SELECT a.*, c.nama_kategori as category_name, l.nama_lokasi as location_name FROM assets a LEFT JOIN categories c ON a.category_id = c.id LEFT JOIN locations l ON a.location_id = l.id $where ORDER BY a.id DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$bindParams = array_merge($params, [$limit, $offset]);
$bindTypes = $types . 'ii';
if (!empty($params)) {
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$assets_list = [];
while ($row = $result->fetch_assoc()) {
    $assets_list[] = $row;
}

$countSql = "SELECT COUNT(*) as total FROM assets a $where";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori");
$locations = $db->query("SELECT * FROM locations ORDER BY nama_lokasi");

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-800">Daftar Aset</h3>
        <div class="flex gap-2">
            <a href="tambah.php" class="btn btn-primary">Tambah Aset</a>
            <a href="import.php" class="btn btn-success">
                <i class="fas fa-upload mr-1"></i>Import CSV
            </a>
            <a href="batch-qrcode.php" class="btn btn-secondary">
                <i class="fas fa-qrcode mr-1"></i>Batch QR
            </a>
        </div>
    </div>

    <form method="GET" class="mb-6">
        <div class="flex flex-wrap gap-2 items-end">
            <div class="flex-grow">
                <input type="text" name="search" class="input input-bordered input-sm w-full" placeholder="Cari aset..." value="<?= sanitize($search) ?>">
            </div>
            <div>
                <select name="status" class="select select-bordered select-sm">
                    <option value="">Semua Status</option>
                    <option value="tersedia" <?= $status_filter === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="dipinjam" <?= $status_filter === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                    <option value="perbaikan" <?= $status_filter === 'perbaikan' ? 'selected' : '' ?>>Perbaikan</option>
                </select>
            </div>
            <div>
                <select name="kategori" class="select select-bordered select-sm">
                    <option value="">Semua Kategori</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" <?= $kategori_filter == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['nama_kategori']) ?></option>
                    <?php endwhile; $categories->data_seek(0); ?>
                </select>
            </div>
            <div>
                <select name="lokasi" class="select select-bordered select-sm">
                    <option value="">Semua Lokasi</option>
                    <?php while ($loc = $locations->fetch_assoc()): ?>
                    <option value="<?= $loc['id'] ?>" <?= $lokasi_filter == $loc['id'] ? 'selected' : '' ?>><?= sanitize($loc['nama_lokasi']) ?></option>
                    <?php endwhile; $locations->data_seek(0); ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Cari</button>
        </div>
    </form>

    <div class="hidden md:block">
        <div class="card bg-white shadow-md mb-8">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets_list as $row): ?>
                        <tr>
                            <td><?= sanitize($row['kode_aset']) ?></td>
                            <td><?= sanitize($row['nama_barang'] ?? '-') ?></td>
                            <td><?= sanitize($row['category_name'] ?? '-') ?></td>
                            <td><?= sanitize($row['location_name'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $row['status'] === 'tersedia' ? 'badge-success' : ($row['status'] === 'dipinjam' ? 'badge-warning' : 'badge-error') ?>">
                                    <?= sanitize($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">Detail</a>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <button type="button" data-delete-name="<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES) ?>" onclick="openDeleteModal('hapus.php', <?= $row['id'] ?>, this.getAttribute('data-delete-name'))" class="btn btn-error btn-sm">Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($assets_list) === 0): ?>
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                    <i class="fas fa-box-open text-5xl mb-4"></i>
                                    <p class="text-lg font-medium text-gray-500">Belum ada aset</p>
                                    <p class="text-sm text-gray-400 mt-1">Tambahkan aset pertama Anda</p>
                                    <a href="tambah.php" class="btn btn-primary btn-sm mt-4">
                                        <i class="fas fa-plus mr-1"></i>Tambah Aset
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="block md:hidden space-y-3 mb-6">
        <?php foreach ($assets_list as $row): ?>
        <div class="card bg-white dark:bg-gray-800 shadow-md p-4">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white"><?= sanitize($row['kode_aset']) ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= sanitize($row['nama_barang'] ?? '-') ?></p>
                </div>
                <span class="badge <?= $row['status'] === 'tersedia' ? 'badge-success' : ($row['status'] === 'dipinjam' ? 'badge-warning' : 'badge-error') ?>">
                    <?= sanitize($row['status']) ?>
                </span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Kategori</span>
                    <p class="text-gray-900 dark:text-white"><?= sanitize($row['category_name'] ?? '-') ?></p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Lokasi</span>
                    <p class="text-gray-900 dark:text-white"><?= sanitize($row['location_name'] ?? '-') ?></p>
                </div>
            </div>
            <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm flex-1">Detail</a>
                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm flex-1">Edit</a>
                <button type="button" onclick="openDeleteModal('hapus.php', <?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_barang'] ?? '', ENT_QUOTES) ?>')" class="btn btn-error btn-sm flex-1">Hapus</button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (count($assets_list) === 0): ?>
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <i class="fas fa-box-open text-5xl mb-4"></i>
            <p class="text-lg font-medium text-gray-500">Belum ada aset</p>
            <a href="tambah.php" class="btn btn-primary btn-sm mt-4">
                <i class="fas fa-plus mr-1"></i>Tambah Aset
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="join">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&kategori=<?= urlencode($kategori_filter) ?>&lokasi=<?= urlencode($lokasi_filter) ?>" class="join-item btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../views/layout.php';
