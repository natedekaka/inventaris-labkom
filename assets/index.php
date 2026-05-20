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
            <a href="tambah.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Tambah Aset</a>
            <a href="import.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-upload mr-1"></i>Import CSV
            </a>
            <a href="batch-qrcode.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-qrcode mr-1"></i>Batch QR
            </a>
        </div>
    </div>

    <form method="GET" class="mb-6">
        <div class="flex flex-wrap gap-2 items-end">
            <div class="flex-grow">
                <input type="text" name="search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Cari aset..." value="<?= sanitize($search) ?>">
            </div>
            <div>
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Semua Status</option>
                    <option value="tersedia" <?= $status_filter === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="dipinjam" <?= $status_filter === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                    <option value="perbaikan" <?= $status_filter === 'perbaikan' ? 'selected' : '' ?>>Perbaikan</option>
                </select>
            </div>
            <div>
                <select name="kategori" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Semua Kategori</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" <?= $kategori_filter == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['nama_kategori']) ?></option>
                    <?php endwhile; $categories->data_seek(0); ?>
                </select>
            </div>
            <div>
                <select name="lokasi" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Semua Lokasi</option>
                    <?php while ($loc = $locations->fetch_assoc()): ?>
                    <option value="<?= $loc['id'] ?>" <?= $lokasi_filter == $loc['id'] ? 'selected' : '' ?>><?= sanitize($loc['nama_lokasi']) ?></option>
                    <?php endwhile; $locations->data_seek(0); ?>
                </select>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200" type="submit">Cari</button>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['kode_aset']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['nama_barang'] ?? '-') ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['category_name'] ?? '-') ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['location_name'] ?? '-') ?></td>
                        <td class="py-4 px-6">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $row['status'] === 'tersedia' ? 'bg-green-100 text-green-800' : ($row['status'] === 'dipinjam' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                <?= sanitize($row['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <a href="detail.php?id=<?= $row['id'] ?>" class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1 rounded text-sm inline-block">Detail</a>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm inline-block">Edit</a>
                            <button type="button" data-delete-name="<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES) ?>" onclick="openDeleteModal('hapus.php', <?= $row['id'] ?>, this.getAttribute('data-delete-name'))" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm inline-block cursor-pointer">Hapus</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex gap-2 justify-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&kategori=<?= urlencode($kategori_filter) ?>&lokasi=<?= urlencode($lokasi_filter) ?>" class="px-3 py-1 rounded-lg border <?= $i === $page ? 'bg-blue-50 text-blue-600 font-semibold border-blue-300' : 'border-gray-300 hover:bg-gray-50' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../views/layout.php';
