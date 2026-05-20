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
$where = '';
$params = [];

if ($search) {
    $where = "WHERE a.nama_barang LIKE ? OR a.kode_aset LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$sql = "SELECT a.*, c.nama_kategori as category_name, l.nama_lokasi as location_name FROM assets a LEFT JOIN categories c ON a.category_id = c.id LEFT JOIN locations l ON a.location_id = l.id $where ORDER BY a.id DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
if ($search) {
    $stmt->bind_param('ssii', ...[...$params, $limit, $offset]);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$countSql = "SELECT COUNT(*) as total FROM assets a $where";
if ($search) {
    $countStmt = $db->prepare($countSql);
    $countStmt->bind_param('ss', ...$params);
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
} else {
    $totalResult = $db->query($countSql);
}
$total = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

ob_start();
?>
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-800">Daftar Aset</h3>
        <div class="flex gap-2">
            <a href="tambah.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">Tambah Aset</a>
            <a href="batch-qrcode.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-qrcode mr-1"></i>Batch QR
            </a>
        </div>
    </div>

    <form method="GET" class="mb-6">
        <div class="flex">
            <input type="text" name="search" class="flex-grow px-4 py-2 border border-gray-300 border-r-0 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Cari aset..." value="<?= sanitize($search) ?>">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-lg transition duration-200" type="submit">Cari</button>
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
                            <a href="hapus.php?id=<?= $row['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm inline-block" onclick="return confirm('Hapus?')">Hapus</a>
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
        <a href="?page=<?= $i ?>&search=<?= sanitize($search) ?>" class="px-3 py-1 rounded-lg border <?= $i === $page ? 'bg-blue-50 text-blue-600 font-semibold border-blue-300' : 'border-gray-300 hover:bg-gray-50' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../views/layout.php';
