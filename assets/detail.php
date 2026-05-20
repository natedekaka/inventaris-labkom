<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Detail Aset';
$db = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT a.*, c.nama_kategori as category_name, l.nama_lokasi as location_name 
                      FROM assets a 
                      LEFT JOIN categories c ON a.category_id = c.id 
                      LEFT JOIN locations l ON a.location_id = l.id 
                      WHERE a.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
    App::setFlash('Aset tidak ditemukan', 'danger');
    App::redirect('/assets/');
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <?php
    $totalPinjam = 0;
    $lastPinjam = '-';
    $totalBiaya = 0;
    $pinjamStmt = $db->prepare("SELECT COUNT(*) as total, MAX(tanggal_pinjam) as last FROM borrowings WHERE asset_id = ?");
    $pinjamStmt->bind_param('i', $id);
    $pinjamStmt->execute();
    $pinjamResult = $pinjamStmt->get_result()->fetch_assoc();
    if ($pinjamResult) {
        $totalPinjam = $pinjamResult['total'] ?? 0;
        $lastPinjam = $pinjamResult['last'] ?? '-';
    }
    $biayaStmt = $db->prepare("SELECT COALESCE(SUM(biaya), 0) as total FROM maintenances WHERE asset_id = ?");
    $biayaStmt->bind_param('i', $id);
    $biayaStmt->execute();
    $biayaResult = $biayaStmt->get_result()->fetch_assoc();
    if ($biayaResult) {
        $totalBiaya = $biayaResult['total'] ?? 0;
    }
    ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 mt-4">
        <div class="bg-white rounded-xl shadow-md p-5 card-hover transition-all duration-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Dipinjam</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $totalPinjam ?>x</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 card-hover transition-all duration-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Terakhir Dipinjam</p>
                    <p class="text-lg font-bold text-gray-800"><?= $lastPinjam !== '-' ? formatTanggal($lastPinjam) : '-' ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 card-hover transition-all duration-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-tools text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Biaya Perbaikan</p>
                    <p class="text-lg font-bold text-gray-800"><?= formatRupiah($totalBiaya) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Detail Aset - <?= sanitize($asset['kode_aset'] . ' - ' . $asset['nama_barang']) ?></h3>
        
        <?php if ($asset['foto']): ?>
        <img src="../uploads/<?= $asset['foto'] ?>" class="max-w-full h-auto mb-6" style="max-width: 300px;">
        <?php endif; ?>
        
        <table class="w-full border-collapse">
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Kode Aset</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['kode_aset']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Nama Barang</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['nama_barang']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Kategori</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['category_name']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Lokasi</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['location_name']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Merek</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['merek']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Model</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['model']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Serial Number</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['serial_number']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Spesifikasi</th><td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($asset['spesifikasi']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Harga</th><td class="py-3 px-4 text-sm text-gray-900"><?= formatRupiah($asset['harga']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Tanggal Beli</th><td class="py-3 px-4 text-sm text-gray-900"><?= formatTanggal($asset['tanggal_beli']) ?></td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Kondisi</th><td class="py-3 px-4">
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $asset['kondisi'] === 'baik' ? 'bg-green-100 text-green-800' : ($asset['kondisi'] === 'rusak_ringan' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                    <?= sanitize($asset['kondisi']) ?>
                </span>
            </td></tr>
            <tr class="border-b"><th class="text-left py-3 px-4 w-48 text-sm font-medium text-gray-700">Status</th><td class="py-3 px-4">
                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $asset['status'] === 'tersedia' ? 'bg-green-100 text-green-800' : ($asset['status'] === 'dipinjam' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                    <?= sanitize($asset['status']) ?>
                </span>
            </td></tr>
        </table>
        
        <div class="flex gap-2 mt-6">
            <a href="/assets/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Kembali</a>
            <a href="edit.php?id=<?= $asset['id'] ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition duration-200">Edit</a>
            <a href="qrcode.php?id=<?= $asset['id'] ?>" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition duration-200">QR Code</a>
            <a href="qrcode.php?id=<?= $asset['id'] ?>&download=1" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-download mr-1"></i>Download QR
            </a>
        </div>
    </div>

    <!-- Asset History Timeline -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h4 class="text-lg font-bold text-gray-800 mb-6">Riwayat Aktivitas</h4>
        <?php
        $tableCheck = $db->query("SHOW TABLES LIKE 'asset_logs'");
        
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $logStmt = $db->prepare("
                SELECT al.*, u.nama as user_nama, u.role
                FROM asset_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.asset_id = ?
                ORDER BY al.created_at DESC
                LIMIT 50
            ");
            $logStmt->bind_param('i', $id);
            $logStmt->execute();
            $logResult = $logStmt->get_result();
        } else {
            $logResult = null;
        }
        
        $actionIcons = [
            'borrow_requested' => ['icon' => 'fa-hand-point-right', 'color' => 'text-yellow-600', 'label' => 'Permintaan Pinjam'],
            'borrow_approved' => ['icon' => 'fa-check-circle', 'color' => 'text-green-600', 'label' => 'Pinjam Disetujui'],
            'borrowed' => ['icon' => 'fa-arrow-right', 'color' => 'text-blue-600', 'label' => 'Dipinjam'],
            'returned' => ['icon' => 'fa-undo', 'color' => 'text-green-600', 'label' => 'Dikembalikan'],
            'maintenance_created' => ['icon' => 'fa-tools', 'color' => 'text-orange-600', 'label' => 'Maintenance Dibuat'],
            'maintenance_completed' => ['icon' => 'fa-check-double', 'color' => 'text-green-600', 'label' => 'Maintenance Selesai'],
            'status_changed' => ['icon' => 'fa-exchange-alt', 'color' => 'text-purple-600', 'label' => 'Status Berubah'],
            'condition_changed' => ['icon' => 'fa-heartbeat', 'color' => 'text-red-600', 'label' => 'Kondisi Berubah'],
            'created' => ['icon' => 'fa-plus-circle', 'color' => 'text-blue-600', 'label' => 'Aset Dibuat']
        ];
        ?>
        
        <?php if ($logResult && $logResult->num_rows > 0): ?>
            <div class="relative">
                <?php while ($log = $logResult->fetch_assoc()): ?>
                    <div class="flex items-start mb-6 last:mb-0">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-4">
                            <i class="fas <?= $actionIcons[$log['action']]['icon'] ?? 'fa-circle' ?> <?= $actionIcons[$log['action']]['color'] ?? 'text-gray-600' ?>"></i>
                        </div>
                        <div class="flex-grow">
                            <div class="flex items-center justify-between">
                                <h5 class="font-semibold text-gray-800">
                                    <?= $actionIcons[$log['action']]['label'] ?? $log['action'] ?>
                                </h5>
                                <span class="text-sm text-gray-500"><?= formatTanggal($log['created_at']) ?></span>
                            </div>
                            <?php if ($log['user_nama']): ?>
                                <p class="text-sm text-gray-600">Oleh: <?= sanitize($log['user_nama']) ?> (<?= $log['role'] ?>)</p>
                            <?php endif; ?>
                            <?php if ($log['details']): ?>
                                <p class="text-sm text-gray-600 mt-1"><?= sanitize($log['details']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php elseif ($logResult === null): ?>
            <p class="text-gray-600 text-sm">Tabel riwayat belum dibuat. Silakan jalankan migrasi database.</p>
        <?php else: ?>
            <p class="text-gray-600 text-sm">Belum ada riwayat untuk aset ini.</p>
        <?php endif; ?>
    </div>

    <!-- Borrowing History -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h4 class="text-lg font-bold text-gray-800 mb-6">Riwayat Peminjaman</h4>
        <?php
        $borrowStmt = $db->prepare("
            SELECT b.*, u.nama as peminjam_nama
            FROM borrowings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.asset_id = ?
            ORDER BY b.created_at DESC
            LIMIT 20
        ");
        $borrowStmt->bind_param('i', $id);
        $borrowStmt->execute();
        $borrowResult = $borrowStmt->get_result();
        
        $borrowStatusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'dipinjam' => 'bg-green-100 text-green-800',
            'dikembalikan' => 'bg-gray-100 text-gray-800',
            'rejected' => 'bg-red-100 text-red-800'
        ];
        ?>
        
        <?php if ($borrowResult && $borrowResult->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Peminjam</th>
                            <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Pinjam</th>
                            <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Rencana Kembali</th>
                            <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Kembali</th>
                            <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Keperluan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($b = $borrowResult->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($b['peminjam_nama'] ?? '-') ?></td>
                            <td class="py-3 px-4 text-sm text-gray-900"><?= formatTanggal($b['tanggal_pinjam']) ?></td>
                            <td class="py-3 px-4 text-sm text-gray-900"><?= formatTanggal($b['rencana_kembali']) ?></td>
                            <td class="py-3 px-4 text-sm text-gray-900"><?= $b['tanggal_kembali'] ? formatTanggal($b['tanggal_kembali']) : '-' ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $borrowStatusColors[$b['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= ucfirst($b['status']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-900"><?= sanitize($b['keperluan'] ?? '-') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600 text-sm">Belum ada riwayat peminjaman untuk aset ini.</p>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
