<!-- Sidebar Navigation -->
<aside class="bg-white w-64 min-h-screen shadow-lg fixed left-0 top-0 pt-16 hidden md:block">
    <div class="p-4">
        <nav class="space-y-2">
            <a href="/" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-primary transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/' ? 'bg-blue-50 text-primary font-semibold' : ''; ?>">
                <i class="fas fa-tachometer-alt w-5"></i>
                <span>Dashboard</span>
            </a>
            <a href="/assets/index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-primary transition duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/assets/') !== false ? 'bg-blue-50 text-primary font-semibold' : ''; ?>">
                <i class="fas fa-desktop w-5"></i>
                <span>Aset</span>
            </a>
            <a href="/borrowings/index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-primary transition duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/borrowings/') !== false ? 'bg-blue-50 text-primary font-semibold' : ''; ?>">
                <i class="fas fa-handshake w-5"></i>
                <span>Peminjaman</span>
            </a>
            <a href="/maintenances/index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-primary transition duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/maintenances/') !== false ? 'bg-blue-50 text-primary font-semibold' : ''; ?>">
                <i class="fas fa-tools w-5"></i>
                <span>Perbaikan</span>
            </a>
            <a href="/reports/index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-primary transition duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'bg-blue-50 text-primary font-semibold' : ''; ?>">
                <i class="fas fa-file-alt w-5"></i>
                <span>Laporan</span>
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/users/index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-primary transition duration-200 <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'bg-blue-50 text-primary font-semibold' : ''; ?>">
                <i class="fas fa-users w-5"></i>
                <span>Users</span>
            </a>
            <?php endif; ?>
        </nav>
    </div>
</aside>
