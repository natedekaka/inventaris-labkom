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
    <!-- Navbar -->
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-gray-700">Hi, <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></span>
                        <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Login</a>
                    <?php endif; ?>
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

    <!-- Flash Messages -->
    <?php
    $flash = App::getFlash();
    if ($flash):
        $type = $flash['type'];
        $message = $flash['message'];
        $colorClass = $type === 'success' ? 'green' : ($type === 'danger' ? 'red' : ($type === 'warning' ? 'yellow' : 'blue'));
    ?>
        <div class="max-w-7xl mx-auto px-4 mt-20">
            <div class="bg-<?= $colorClass ?>-100 border border-<?= $colorClass ?>-400 text-<?= $colorClass ?>-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="pt-20 pb-8">
        <?php echo $content; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2026 Inventaris Lab Komputer. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }
    </script>
</body>
</html>
