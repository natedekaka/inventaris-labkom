<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();

$title = 'Profil Saya';
$db = db();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$borrowStmt = $db->prepare("SELECT b.*, a.nama_barang as nama_aset, a.kode_aset FROM borrowings b JOIN assets a ON b.asset_id = a.id WHERE b.user_id = ? ORDER BY b.id DESC");
$borrowStmt->bind_param('i', $_SESSION['user_id']);
$borrowStmt->execute();
$borrowings = $borrowStmt->get_result();

$roleLabels = [
    'admin' => 'Admin',
    'user' => 'User (Siswa)',
    'viewer' => 'Viewer (Guru)',
    'lab_assistant' => 'Lab Assistant',
    'operator' => 'Operator'
];

ob_start();
?>
<div class="max-w-5xl mx-auto px-4">
    <div class="card bg-white shadow-md p-6 mb-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-2xl text-blue-600"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800"><?= sanitize($user['nama']) ?></h3>
                <span class="text-sm text-gray-500"><?= $roleLabels[$user['role']] ?? ucfirst($user['role']) ?></span>
            </div>
            <a href="edit.php" class="ml-auto btn btn-primary">
                <i class="fas fa-edit mr-1"></i>Edit Profil
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-500">Nama Lengkap</p>
                <p class="text-gray-900 font-medium"><?= sanitize($user['nama']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">NIS</p>
                <p class="text-gray-900 font-medium"><?= $user['nis'] ? sanitize($user['nis']) : '-' ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Role</p>
                <p class="text-gray-900 font-medium"><?= $roleLabels[$user['role']] ?? ucfirst($user['role']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Bergabung Sejak</p>
                <p class="text-gray-900 font-medium"><?= formatTanggal($user['created_at']) ?></p>
            </div>
        </div>
    </div>

    <div class="card bg-white shadow-md p-6 mb-8">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Riwayat Peminjaman</h4>
        <?php if ($borrowings->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Aset</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Aset</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pinjam</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Rencana Kembali</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Kembali</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($row = $borrowings->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['kode_aset']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= sanitize($row['nama_aset']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($row['tanggal_pinjam']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($row['rencana_kembali']) ?></td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= formatTanggal($row['tanggal_kembali']) ?></td>
                        <td class="py-4 px-6">
                            <?php
                            $badgeColors = [
                                'dikembalikan' => 'bg-green-100 text-green-800',
                                'dipinjam' => 'bg-yellow-100 text-yellow-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'approved' => 'bg-blue-100 text-blue-800',
                                'pending' => 'bg-blue-100 text-blue-800'
                            ];
                            $statusLabels = [
                                'dikembalikan' => 'Dikembalikan',
                                'dipinjam' => 'Dipinjam',
                                'rejected' => 'Ditolak',
                                'approved' => 'Disetujui',
                                'pending' => 'Pending'
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $badgeColors[$row['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= $statusLabels[$row['status']] ?? ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-900"><?= $row['denda'] ? formatRupiah($row['denda']) : '-' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
            <p>Belum ada riwayat peminjaman</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
