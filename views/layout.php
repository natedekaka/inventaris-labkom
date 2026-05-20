<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Inventaris Lab Komputer'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3b82f6',
                        accent: '#f59e0b'
                    }
                }
            }
        }
    </script>
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Navbar (logged in users only) -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-desktop text-2xl text-primary mr-2"></i>
                    <span class="font-bold text-xl text-gray-800">Inventaris Lab</span>
                </div>
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="/" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="/assets/index.php" class="text-gray-700 hover:text-primary">Aset</a>
                    <a href="/borrowings/index.php" class="text-gray-700 hover:text-primary">Peminjaman</a>
                    <a href="/maintenances/index.php" class="text-gray-700 hover:text-primary">Perbaikan</a>
                    <a href="/reports/index.php" class="text-gray-700 hover:text-primary">Laporan</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/users/index.php" class="text-gray-700 hover:text-primary">Users</a>
                    <?php endif; ?>
                    <a href="/profile/" class="text-gray-700 hover:text-primary">
                        <i class="fas fa-user-circle mr-1"></i><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
                <button class="md:hidden" onclick="toggleMenu()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-white border-t">
            <a href="/" class="block px-4 py-2 text-gray-700">Dashboard</a>
            <a href="/assets/index.php" class="block px-4 py-2 text-gray-700">Aset</a>
            <a href="/borrowings/index.php" class="block px-4 py-2 text-gray-700">Peminjaman</a>
            <a href="/maintenances/index.php" class="block px-4 py-2 text-gray-700">Perbaikan</a>
            <a href="/reports/index.php" class="block px-4 py-2 text-gray-700">Laporan</a>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php
    $flash = App::getFlash();
    if ($flash):
        $type = $flash['type'];
        $message = $flash['message'];
        $colorClass = $type === 'success' ? 'green' : ($type === 'danger' ? 'red' : ($type === 'warning' ? 'yellow' : 'blue'));
        $flashTopClass = isset($_SESSION['user_id']) ? 'mt-20' : 'mt-4';
    ?>
        <div class="max-w-7xl mx-auto px-4 <?= $flashTopClass ?>">
            <div class="bg-<?= $colorClass ?>-100 border border-<?= $colorClass ?>-400 text-<?= $colorClass ?>-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="<?= isset($_SESSION['user_id']) ? 'pt-20 pb-8' : '' ?>" <?= isset($_SESSION['user_id']) ? 'style="padding-top:5rem"' : '' ?>>
        <?php echo $content; ?>
    </main>

    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2026 Inventaris Lab Komputer. All rights reserved.</p>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeDeleteModal()"></div>
        <div class="bg-white rounded-xl shadow-2xl p-6 z-10 max-w-md w-full mx-4 relative transform transition-all">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-2xl text-red-600"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Hapus</h4>
                <p class="text-gray-600" id="deleteModalMessage">Apakah Anda yakin ingin menghapus data ini?</p>
                <p class="text-sm text-red-500 mt-2" id="deleteModalDetail"></p>
            </div>
            <form method="POST" id="deleteModalForm">
                <?= App::csrfField() ?>
                <input type="hidden" name="delete_id" id="deleteModalId" value="">
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200 font-medium">
                        <i class="fas fa-trash mr-1"></i> Ya, Hapus
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200 font-medium">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }

        function openDeleteModal(actionUrl, deleteId, itemName) {
            document.getElementById('deleteModalForm').action = actionUrl;
            document.getElementById('deleteModalId').value = deleteId;
            document.getElementById('deleteModalMessage').textContent = itemName 
                ? 'Apakah Anda yakin ingin menghapus "' + itemName + '"?' 
                : 'Apakah Anda yakin ingin menghapus data ini?';
            document.getElementById('deleteModalDetail').textContent = itemName ? '' : 'Tindakan ini tidak dapat dibatalkan.';
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });
    </script>
</body>
</html>
