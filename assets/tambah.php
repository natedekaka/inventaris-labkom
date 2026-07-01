<?php
require_once __DIR__ . '/../config/init_sekolah.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/functions.php';

App::requireLogin();
App::requireRole(['admin', 'lab_assistant']);

$title = 'Tambah Aset';
$db = db();

$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori");
$locations = $db->query("SELECT * FROM locations ORDER BY nama_lokasi");

$defaultKode = generateKodeAset('INV');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!App::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        App::setFlash('Invalid request', 'danger');
        App::redirect('/assets/tambah.php');
    }
    $kode = !empty(trim($_POST['kode_aset'] ?? '')) ? trim($_POST['kode_aset']) : $defaultKode;
    $nama_barang = $_POST['nama_barang'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $merek = $_POST['merek'] ?? '';
    $model = $_POST['model'] ?? '';
    $serial_number = !empty($_POST['serial_number']) ? $_POST['serial_number'] : null;
    $spesifikasi = $_POST['spesifikasi'] ?? '';
    $harga = (float)($_POST['harga'] ?? 0);
    if ($harga < 0) $harga = 0;
    $tanggal_beli = $_POST['tanggal_beli'] ?? date('Y-m-d');
    $kondisi = $_POST['kondisi'] ?? 'baik';
    if (!in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'])) $kondisi = 'baik';
    $status = $_POST['status'] ?? 'tersedia';
    if (!in_array($status, ['tersedia', 'dipinjam', 'perbaikan'])) $status = 'tersedia';
    $location_id = (int)($_POST['location_id'] ?? 0);
    $garansi_sampai = !empty($_POST['garansi_sampai']) ? $_POST['garansi_sampai'] : null;

    if (empty($kode)) {
        App::setFlash('Kode aset harus diisi', 'danger');
        App::redirect('/assets/tambah.php');
    }
    if (strlen($kode) > 50) {
        App::setFlash('Kode aset maksimal 50 karakter', 'danger');
        App::redirect('/assets/tambah.php');
    }
    $check = $db->prepare("SELECT id FROM assets WHERE kode_aset = ?");
    $check->bind_param('s', $kode);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        App::setFlash('Kode aset "' . sanitize($kode) . '" sudah digunakan', 'danger');
        App::redirect('/assets/tambah.php');
    }

    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_types)) {
            App::setFlash('Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, atau WEBP.', 'danger');
            App::redirect('/assets/tambah.php');
        }

        if ($_FILES['foto']['size'] > 2097152) {
            App::setFlash('Ukuran file maksimal 2MB', 'danger');
            App::redirect('/assets/tambah.php');
        }

        $check = getimagesize($_FILES['foto']['tmp_name']);
        if ($check === false) {
            App::setFlash('File bukan gambar yang valid', 'danger');
            App::redirect('/assets/tambah.php');
        }

        $foto = 'asset_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto);
    }

    $stmt = $db->prepare("
        INSERT INTO assets
        (kode_aset, nama_barang, category_id, merek, model, serial_number, spesifikasi, harga, tanggal_beli, kondisi, status, location_id, foto, garansi_sampai)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sisisssdsssisd',
        $kode, $nama_barang, $category_id, $merek, $model, $serial_number, $spesifikasi, $harga, $tanggal_beli, $kondisi, $status, $location_id, $foto, $garansi_sampai
    );

    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['nama'], 'create', 'assets', $db->getLastId(), 'Menambahkan aset: ' . $nama_barang);
        App::setFlash('Aset berhasil ditambahkan', 'success');
        App::redirect('/assets/');
    } else {
        App::setFlash('Gagal menambahkan aset', 'danger');
    }
}

ob_start();
?>
<div class="max-w-4xl mx-auto px-4">
    <div class="card bg-white shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Tambah Aset</h3>
        <form method="POST" enctype="multipart/form-data">
            <?= App::csrfField() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Aset</label>
                <div class="flex gap-2">
                    <input type="text" name="kode_aset" id="kode_aset" class="input input-bordered flex-1" value="<?= sanitize($defaultKode) ?>" required maxlength="50" oninput="cekDuplikatKode()">
                    <button type="button" onclick="generateKodeBaru()" class="btn btn-primary btn-sm" title="Generate ulang kode otomatis">
                        <i class="fas fa-sync-alt mr-1"></i>Generate
                    </button>
                </div>
                <p id="kodeStatus" class="text-sm mt-1 text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>Kosongkan atau klik Generate untuk isi otomatis
                </p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Aset</label>
                <input type="text" name="nama_barang" class="input input-bordered w-full" required>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <div class="flex gap-2">
                        <select name="category_id" class="select select-bordered flex-1" required>
                            <option value="">Pilih Kategori</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>"><?= sanitize($cat['nama_kategori']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="button" onclick="openTambahModal('kategori')" class="btn btn-success btn-sm" title="Tambah Kategori Baru">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <div class="flex gap-2">
                        <select name="location_id" class="select select-bordered flex-1" required>
                            <option value="">Pilih Lokasi</option>
                            <?php while ($loc = $locations->fetch_assoc()): ?>
                            <option value="<?= $loc['id'] ?>"><?= sanitize($loc['nama_lokasi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="button" onclick="openTambahModal('lokasi')" class="btn btn-success btn-sm" title="Tambah Lokasi Baru">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Merek</label>
                    <input type="text" name="merek" class="input input-bordered w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" class="input input-bordered w-full">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                <input type="text" name="serial_number" class="input input-bordered w-full">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Spesifikasi</label>
                <textarea name="spesifikasi" class="textarea textarea-bordered w-full" rows="3"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga</label>
                    <input type="number" name="harga" class="input input-bordered w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Beli</label>
                    <input type="date" name="tanggal_beli" class="input input-bordered w-full" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Garansi Sampai</label>
                    <input type="date" name="garansi_sampai" class="input input-bordered w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi</label>
                    <select name="kondisi" class="select select-bordered w-full">
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="select select-bordered w-full">
                        <option value="tersedia">Tersedia</option>
                        <option value="dipinjam">Dipinjam</option>
                        <option value="perbaikan">Perbaikan</option>
                    </select>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                <input type="file" name="foto" class="file-input file-input-bordered w-full" accept="image/*">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="/assets/" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Kategori/Lokasi -->
<div id="tambahModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeTambahModal()"></div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 z-10 max-w-md w-full mx-4 relative transform transition-all">
        <div class="text-center mb-4">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-plus text-2xl text-green-600"></i>
            </div>
            <h4 class="text-xl font-bold text-gray-800 dark:text-white mb-2" id="tambahModalTitle">Tambah</h4>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" id="tambahModalLabel">Nama</label>
            <input type="text" id="tambahModalInput" class="input input-bordered w-full" placeholder="Masukkan nama" maxlength="100">
            <p id="tambahModalError" class="text-red-500 text-sm mt-1 hidden"></p>
        </div>
        <input type="hidden" id="tambahModalType" value="">
        <div class="flex gap-2">
            <button id="tambahModalBtn" onclick="submitTambahModal()" class="btn btn-success flex-1">
                <i class="fas fa-save mr-1"></i> Simpan
            </button>
            <button type="button" onclick="closeTambahModal()" class="btn btn-ghost flex-1">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
function openTambahModal(type) {
    var modal = document.getElementById('tambahModal');
    var title = document.getElementById('tambahModalTitle');
    var label = document.getElementById('tambahModalLabel');
    var input = document.getElementById('tambahModalInput');
    var error = document.getElementById('tambahModalError');

    document.getElementById('tambahModalType').value = type;

    if (type === 'kategori') {
        title.textContent = 'Tambah Kategori Baru';
        label.textContent = 'Nama Kategori';
        input.placeholder = 'Contoh: Printer, Scanner, UPS';
    } else {
        title.textContent = 'Tambah Lokasi Baru';
        label.textContent = 'Nama Lokasi';
        input.placeholder = 'Contoh: Lab 3, Lab 4, Ruang Guru';
    }

    input.value = '';
    error.classList.add('hidden');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(function() { input.focus(); }, 100);
}

function closeTambahModal() {
    document.getElementById('tambahModal').classList.add('hidden');
    document.getElementById('tambahModal').classList.remove('flex');
}

function submitTambahModal() {
    var type = document.getElementById('tambahModalType').value;
    var nama = document.getElementById('tambahModalInput').value.trim();
    var error = document.getElementById('tambahModalError');
    var btn = document.getElementById('tambahModalBtn');

    if (!nama) {
        error.textContent = 'Nama tidak boleh kosong';
        error.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    error.classList.add('hidden');

    var csrfField = document.querySelector('form input[name="csrf_token"]');
    var csrfToken = csrfField ? csrfField.value : '';
    var url = type === 'kategori' ? 'tambah_kategori.php' : 'tambah_lokasi.php';

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nama=' + encodeURIComponent(nama) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            var selectName = type === 'kategori' ? 'category_id' : 'location_id';
            var select = document.querySelector('select[name="' + selectName + '"]');
            var option = document.createElement('option');
            option.value = data.id;
            option.textContent = data.nama;
            option.selected = true;
            select.appendChild(option);
            closeTambahModal();
        } else {
            error.textContent = data.message || 'Gagal menyimpan';
            error.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
        }
    })
    .catch(function() {
        error.textContent = 'Terjadi kesalahan koneksi';
        error.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeTambahModal();
});

// ===== KODE ASET =====
var kodeTimeout = null;

function cekDuplikatKode() {
    var input = document.getElementById('kode_aset');
    var status = document.getElementById('kodeStatus');
    var kode = input.value.trim();

    clearTimeout(kodeTimeout);

    if (!kode) {
        status.className = 'text-sm mt-1 text-gray-500';
        status.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Kosongkan atau klik Generate untuk isi otomatis';
        return;
    }

    status.className = 'text-sm mt-1 text-gray-500';
    status.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memeriksa ketersediaan...';

    kodeTimeout = setTimeout(function() {
        fetch('cek_duplikat_kode.php?kode=' + encodeURIComponent(kode))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.available) {
                    status.className = 'text-sm mt-1 text-green-600';
                    status.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Kode tersedia';
                } else {
                    status.className = 'text-sm mt-1 text-red-600';
                    status.innerHTML = '<i class="fas fa-times-circle mr-1"></i>' + data.message;
                }
            })
            .catch(function() {
                status.className = 'text-sm mt-1 text-gray-500';
                status.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Gagal memeriksa ketersediaan';
            });
    }, 400);
}

function generateKodeBaru() {
    var input = document.getElementById('kode_aset');
    var status = document.getElementById('kodeStatus');

    status.className = 'text-sm mt-1 text-blue-600';
    status.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Mengenerate kode...';

    fetch('generate_kode.php')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            input.value = data.kode;
            cekDuplikatKode();
        })
        .catch(function() {
            status.className = 'text-sm mt-1 text-red-600';
            status.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Gagal generate kode';
        });
}
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
