<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin']);

$title = 'Import Aset dari CSV';
$db = db();

$previewData = null;
$csvRawData = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/assets/import.php');
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        App::setFlash('File harus berformat CSV', 'danger');
        App::redirect('/assets/import.php');
    }

    $csvRawData = file_get_contents($file);
    $lines = explode("\n", trim($csvRawData));
    $header = str_getcsv(array_shift($lines));
    $header = array_map('trim', $header);
    $expectedHeader = ['kode_aset', 'nama_barang', 'category_id', 'location_id', 'kondisi', 'harga', 'keterangan'];

    if (count(array_intersect($header, $expectedHeader)) < 2) {
        App::setFlash('Format CSV tidak sesuai. Header harus: kode_aset, nama_barang, category_id, location_id, kondisi, harga, keterangan', 'danger');
        App::redirect('/assets/import.php');
    }

    $rowCount = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        if ($rowCount >= 10) break;
        $row = str_getcsv($line);
        $row = array_map('trim', $row);
        $previewData[] = array_combine($header, $row);
        $rowCount++;
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Import Aset dari CSV</h3>

        <?php if (!empty($_SESSION['import_errors'])): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h4 class="font-semibold text-red-800 mb-2">Detail Error (<?= count($_SESSION['import_errors']) ?>):</h4>
            <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($_SESSION['import_errors'] as $err): ?>
                <li class="text-sm text-red-700"><?= sanitize($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['import_errors']); ?>
        <?php endif; ?>

        <?php if ($previewData): ?>
        <div class="mb-6">
            <h4 class="font-semibold text-gray-700 mb-3">Preview Data (10 baris pertama)</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Kode Aset</th>
                            <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Nama Barang</th>
                            <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Kategori ID</th>
                            <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Lokasi ID</th>
                            <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Kondisi</th>
                            <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Harga</th>
                            <th class="py-2 px-3 text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($previewData as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-3"><?= sanitize($row['kode_aset']) ?></td>
                            <td class="py-2 px-3"><?= sanitize($row['nama_barang']) ?></td>
                            <td class="py-2 px-3"><?= sanitize($row['category_id'] ?? '-') ?></td>
                            <td class="py-2 px-3"><?= sanitize($row['location_id'] ?? '-') ?></td>
                            <td class="py-2 px-3"><?= sanitize($row['kondisi'] ?? '-') ?></td>
                            <td class="py-2 px-3"><?= sanitize($row['harga'] ?? '-') ?></td>
                            <td class="py-2 px-3"><?= sanitize($row['keterangan'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">* Menampilkan maksimal 10 baris pertama</p>
        </div>

        <form method="POST" action="import_process.php">
            <?= App::csrfField() ?>
            <input type="hidden" name="csv_data" value="<?= sanitize($csvRawData) ?>">
            <div class="flex gap-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-check mr-1"></i>Import Data
                </button>
                <a href="import.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Batal</a>
            </div>
        </form>

        <?php else: ?>

        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-semibold text-blue-800 mb-2">Format CSV:</h4>
            <p class="text-sm text-blue-700 mb-2">Baris pertama harus berupa header dengan kolom:</p>
            <code class="block text-sm bg-white p-3 rounded border border-blue-200">
                kode_aset,nama_barang,category_id,location_id,kondisi,harga,keterangan
            </code>
            <p class="text-sm text-blue-700 mt-2">Contoh data:</p>
            <code class="block text-sm bg-white p-3 rounded border border-blue-200">
                kode_aset,nama_barang,category_id,location_id,kondisi,harga,keterangan<br>
                INV-101,Monitor LG 24",1,2,baik,2500000,Monitor baru<br>
                INV-102,Keyboard Mechanical,3,1,baik,500000,Untuk Lab 1
            </code>
            <ul class="list-disc pl-5 mt-2 text-sm text-blue-700 space-y-1">
                <li><strong>kode_aset</strong> (wajib, harus unik)</li>
                <li><strong>nama_barang</strong> (wajib)</li>
                <li><strong>category_id</strong> ID kategori (harus ada di tabel categories, default: null)</li>
                <li><strong>location_id</strong> ID lokasi (harus ada di tabel locations, default: null)</li>
                <li><strong>kondisi</strong> baik / rusak_ringan / rusak_berat (default: baik)</li>
                <li><strong>harga</strong> angka desimal (default: 0)</li>
                <li><strong>keterangan</strong> teks opsional</li>
            </ul>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?= App::csrfField() ?>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File CSV</label>
                <input type="file" name="csv_file" accept=".csv" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-eye mr-1"></i>Preview
                </button>
                <a href="/assets/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">Kembali</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../views/layout.php';
