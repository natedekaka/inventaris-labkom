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
            darkMode: 'class',
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
        body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        #searchDropdown {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        #searchDropdown::-webkit-scrollbar {
            width: 6px;
        }
        #searchDropdown::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 dark:text-white">
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Navbar (logged in users only) -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-desktop text-2xl text-primary mr-2"></i>
                    <span class="font-bold text-xl text-gray-800 dark:text-white">Inventaris Lab</span>
                </div>

                <!-- Global Search -->
                <div class="hidden md:flex items-center relative mx-4 flex-1 max-w-md">
                    <div class="relative w-full">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" id="globalSearch" placeholder="Cari aset atau user..." class="pl-9 pr-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent w-full" autocomplete="off">
                    </div>
                    <div id="searchDropdown" class="absolute top-full mt-1 left-0 right-0 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 hidden max-h-80 overflow-y-auto z-50"></div>
                </div>

                <div class="hidden md:flex space-x-4 items-center">
                    <a href="/" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary">Dashboard</a>
                    <a href="/assets/index.php" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary">Aset</a>
                    <a href="/borrowings/index.php" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary">Peminjaman</a>
                    <a href="/maintenances/index.php" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary">Perbaikan</a>
                    <a href="/reports/index.php" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary">Laporan</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/users/index.php" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary">Users</a>
                    <?php endif; ?>

                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" class="text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary focus:outline-none text-lg" aria-label="Toggle Dark Mode">
                        <i id="darkModeIcon" class="fas fa-moon"></i>
                    </button>

                    <!-- Overdue Notification -->
                    <div class="relative" id="notificationContainer">
                        <button id="notificationBell" class="relative text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary text-lg focus:outline-none" aria-label="Notifikasi">
                            <i class="fas fa-bell"></i>
                            <span id="overdueBadge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto"></div>
                    </div>

                    <a href="/profile/" class="text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary">
                        <i class="fas fa-user-circle mr-1"></i><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>
                    </a>
                    <a href="/logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
                <button class="md:hidden" onclick="toggleMenu()">
                    <i class="fas fa-bars text-xl dark:text-white"></i>
                </button>
            </div>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700">
            <a href="/" class="block px-4 py-2 text-gray-700 dark:text-gray-300">Dashboard</a>
            <a href="/assets/index.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300">Aset</a>
            <a href="/borrowings/index.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300">Peminjaman</a>
            <a href="/maintenances/index.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300">Perbaikan</a>
            <a href="/reports/index.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300">Laporan</a>
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
            <div class="bg-<?= $colorClass ?>-100 dark:bg-<?= $colorClass ?>-900 border border-<?= $colorClass ?>-400 dark:border-<?= $colorClass ?>-700 text-<?= $colorClass ?>-700 dark:text-<?= $colorClass ?>-200 px-4 py-3 rounded relative" role="alert">
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
    <footer class="bg-gray-800 dark:bg-gray-900 text-white py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2026 Inventaris Lab Komputer. All rights reserved.</p>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeDeleteModal()"></div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 z-10 max-w-md w-full mx-4 relative transform transition-all">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-2xl text-red-600"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Konfirmasi Hapus</h4>
                <p class="text-gray-600 dark:text-gray-400" id="deleteModalMessage">Apakah Anda yakin ingin menghapus data ini?</p>
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
        // ===== DARK MODE =====
        (function() {
            var html = document.documentElement;
            var stored = localStorage.getItem('darkMode');
            if (stored === 'true') {
                html.classList.add('dark');
            }
        })();

        function toggleDarkMode() {
            var html = document.documentElement;
            var isDark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            var icon = document.getElementById('darkModeIcon');
            if (icon) {
                icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // ===== GLOBAL SEARCH =====
        var searchTimeout = null;

        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('globalSearch');
            var searchDropdown = document.getElementById('searchDropdown');

            // Update dark mode icon on page load
            var html = document.documentElement;
            var icon = document.getElementById('darkModeIcon');
            if (icon) {
                icon.className = html.classList.contains('dark') ? 'fas fa-sun' : 'fas fa-moon';
            }

            // Dark mode toggle button
            var toggleBtn = document.getElementById('darkModeToggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleDarkMode);
            }

            // Global search functionality
            if (searchInput && searchDropdown) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    var q = this.value.trim();
                    if (q.length < 2) {
                        searchDropdown.classList.add('hidden');
                        return;
                    }
                    searchTimeout = setTimeout(function() {
                        fetch('/search.php?q=' + encodeURIComponent(q))
                            .then(function(res) { return res.json(); })
                            .then(function(data) {
                                searchDropdown.innerHTML = '';
                                if (data.assets.length === 0 && data.users.length === 0) {
                                    searchDropdown.innerHTML = '<div class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">Tidak ada hasil ditemukan</div>';
                                } else {
                                    if (data.assets.length > 0) {
                                        var title = document.createElement('div');
                                        title.className = 'px-4 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-700';
                                        title.textContent = 'Aset';
                                        searchDropdown.appendChild(title);
                                        data.assets.forEach(function(asset) {
                                            var a = document.createElement('a');
                                            a.href = '/assets/detail.php?id=' + asset.id;
                                            a.className = 'flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600';
                                            a.innerHTML = '<i class="fas fa-box text-gray-400 mr-3 w-4"></i><span><strong>' + escapeHtml(asset.kode_aset) + '</strong> - ' + escapeHtml(asset.nama_barang) + '</span>';
                                            searchDropdown.appendChild(a);
                                        });
                                    }
                                    if (data.users.length > 0) {
                                        var title = document.createElement('div');
                                        title.className = 'px-4 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-700';
                                        title.textContent = 'User';
                                        searchDropdown.appendChild(title);
                                        data.users.forEach(function(user) {
                                            var a = document.createElement('a');
                                            a.href = '/profile/';
                                            a.className = 'flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600';
                                            a.innerHTML = '<i class="fas fa-user text-gray-400 mr-3 w-4"></i><span>' + escapeHtml(user.nama) + '</span>';
                                            searchDropdown.appendChild(a);
                                        });
                                    }
                                }
                                searchDropdown.classList.remove('hidden');
                            })
                            .catch(function() {
                                searchDropdown.innerHTML = '<div class="px-4 py-3 text-red-500 text-sm">Terjadi kesalahan</div>';
                                searchDropdown.classList.remove('hidden');
                            });
                    }, 300);
                });

                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                        searchDropdown.classList.add('hidden');
                    }
                });

                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        searchDropdown.classList.add('hidden');
                        searchInput.blur();
                    }
                });
            }

            // ===== OVERDUE NOTIFICATION =====
            function fetchOverdueCount() {
                fetch('/notifications.php')
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        var badge = document.getElementById('overdueBadge');
                        if (badge) {
                            if (data.count > 0) {
                                badge.textContent = data.count > 99 ? '99+' : data.count;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }
                    })
                    .catch(function() {});
            }

            function openNotificationDropdown() {
                var dropdown = document.getElementById('notificationDropdown');
                if (!dropdown) return;
                if (!dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    return;
                }
                dropdown.classList.remove('hidden');

                var severityStyles = {
                    critical: { iconBg: 'bg-red-100', iconColor: 'text-red-600', badgeBg: 'bg-red-100', badgeColor: 'text-red-700', label: 'Kritis' },
                    warning: { iconBg: 'bg-orange-100', iconColor: 'text-orange-600', badgeBg: 'bg-orange-100', badgeColor: 'text-orange-700', label: 'Warning' },
                    info: { iconBg: 'bg-yellow-100', iconColor: 'text-yellow-600', badgeBg: 'bg-yellow-100', badgeColor: 'text-yellow-700', label: 'Info' }
                };

                fetch('/notifications.php')
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        dropdown.innerHTML = '';
                        var header = document.createElement('div');
                        header.className = 'px-4 py-3 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10';
                        header.innerHTML = '<h4 class="font-semibold text-gray-800 dark:text-white">Notifikasi Terlambat</h4>';
                        dropdown.appendChild(header);

                        if (data.notifications.length === 0) {
                            var empty = document.createElement('div');
                            empty.className = 'px-4 py-8 text-center text-gray-500 dark:text-gray-400';
                            empty.innerHTML = '<i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i><p class="text-sm">Semua peminjaman tepat waktu \u2705</p>';
                            dropdown.appendChild(empty);
                        } else {
                            data.notifications.forEach(function(n) {
                                var s = severityStyles[n.severity] || severityStyles.info;
                                var a = document.createElement('a');
                                a.href = '/borrowings/pengembalian.php?id=' + n.id;
                                a.className = 'block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700';
                                a.innerHTML = '<div class="flex items-start gap-3">'
                                    + '<div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ' + s.iconBg + '">'
                                    + '<i class="fas fa-exclamation-circle ' + s.iconColor + ' text-sm"></i>'
                                    + '</div>'
                                    + '<div class="flex-1 min-w-0">'
                                    + '<p class="text-sm font-medium text-gray-900 dark:text-white truncate">' + escapeHtml(n.nama_peminjam) + '</p>'
                                    + '<p class="text-xs text-gray-500 dark:text-gray-400 truncate">' + escapeHtml(n.nama_aset) + ' (' + escapeHtml(n.kode_aset) + ')</p>'
                                    + '<div class="flex items-center gap-2 mt-1">'
                                    + '<span class="text-xs text-gray-400">Terlambat ' + n.hari_terlambat + ' hari</span>'
                                    + '<span class="px-1.5 py-0.5 text-xs font-semibold rounded-full ' + s.badgeBg + ' ' + s.badgeColor + '">' + s.label + '</span>'
                                    + '</div>'
                                    + '</div>'
                                    + '<i class="fas fa-chevron-right text-gray-400 text-xs mt-2"></i>'
                                    + '</div>';
                                dropdown.appendChild(a);
                            });

                            var footer = document.createElement('a');
                            footer.href = '/borrowings/index.php?filter=dipinjam';
                            footer.className = 'block px-4 py-3 text-center text-sm font-medium text-primary hover:bg-gray-50 dark:hover:bg-gray-700 rounded-b-xl';
                            footer.innerHTML = 'Lihat Semua <i class="fas fa-arrow-right ml-1"></i>';
                            dropdown.appendChild(footer);
                        }
                    })
                    .catch(function() {
                        dropdown.innerHTML = '<div class="px-4 py-8 text-center text-red-500 text-sm">Gagal memuat notifikasi</div>';
                    });
            }

            var bellBtn = document.getElementById('notificationBell');
            if (bellBtn) {
                bellBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openNotificationDropdown();
                });
            }

            document.addEventListener('click', function(e) {
                var container = document.getElementById('notificationContainer');
                var dropdown = document.getElementById('notificationDropdown');
                if (container && dropdown && !container.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    var dropdown = document.getElementById('notificationDropdown');
                    if (dropdown) {
                        dropdown.classList.add('hidden');
                    }
                }
            });

            fetchOverdueCount();
            setInterval(fetchOverdueCount, 60000);
        });

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        // ===== EXISTING FUNCTIONS =====
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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });
    </script>
</body>
</html>
