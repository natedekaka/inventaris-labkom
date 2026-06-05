<!DOCTYPE html>
<html lang="id" data-theme="inventaris">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Inventaris Lab Komputer'; ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { transition: background-color 0.3s ease, color 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        #searchDropdown { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        #searchDropdown::-webkit-scrollbar { width: 6px; }
        #searchDropdown::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover { transform: translateX(4px); }
    </style>
</head>
<body class="bg-base-200 dark:bg-gray-900 dark:text-white min-h-screen">

<?php if (isset($_SESSION['user_id'])): ?>

<!-- ===== DRAWER (Sidebar + Content) ===== -->
<div class="drawer lg:drawer-open">
    <input id="sidebar-toggle" type="checkbox" class="drawer-toggle" />

    <!-- ===== MAIN CONTENT ===== -->
    <div class="drawer-content flex flex-col min-h-screen">
        <!-- Top Bar -->
        <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between h-16 px-4 lg:px-6">
                <!-- Left: Hamburger + Logo (mobile) -->
                <div class="flex items-center gap-3">
                    <label for="sidebar-toggle" class="btn btn-ghost btn-square lg:hidden" aria-label="Toggle sidebar">
                        <i class="fas fa-bars text-xl"></i>
                    </label>
                    <div class="flex items-center gap-2 lg:hidden">
                        <i class="fas fa-desktop text-xl text-primary"></i>
                        <span class="font-bold text-lg">Inventaris Lab</span>
                    </div>
                </div>

                <!-- Center: Global Search -->
                <div class="hidden sm:flex items-center relative flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" id="globalSearch" placeholder="Cari aset atau user..."
                            class="input input-bordered input-sm w-full pl-9 bg-base-200 dark:bg-gray-700 text-sm">
                    </div>
                    <div id="searchDropdown" class="absolute top-full mt-1 left-0 right-0 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 hidden max-h-80 overflow-y-auto z-50"></div>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2">
                    <!-- Dark Mode -->
                    <button id="darkModeToggle" class="btn btn-ghost btn-square btn-sm" aria-label="Toggle Dark Mode">
                        <i id="darkModeIcon" class="fas fa-moon"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="relative" id="notificationContainer">
                        <button id="notificationBell" class="btn btn-ghost btn-square btn-sm relative" aria-label="Notifikasi">
                            <i class="fas fa-bell"></i>
                            <span id="overdueBadge" class="absolute -top-1 -right-1 badge badge-error badge-xs text-white font-bold hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-96 overflow-y-auto"></div>
                    </div>

                    <!-- Profile -->
                    <div class="dropdown dropdown-end hidden sm:block">
                        <label tabindex="0" class="btn btn-ghost btn-sm gap-2">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span class="hidden md:inline"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></span>
                            <i class="fas fa-chevron-down text-xs opacity-60"></i>
                        </label>
                        <ul tabindex="0" class="dropdown-content menu menu-sm bg-white dark:bg-gray-800 rounded-box shadow-xl border border-gray-200 dark:border-gray-700 z-50 w-44 p-2">
                            <li><a href="/profile/"><i class="fas fa-user w-4"></i>Profil Saya</a></li>
                            <li><a href="/profile/edit.php"><i class="fas fa-edit w-4"></i>Edit Profil</a></li>
                            <li class="divider my-1"></li>
                            <li><a href="/logout.php" class="text-error"><i class="fas fa-sign-out-alt w-4"></i>Logout</a></li>
                        </ul>
                    </div>

                    <!-- Mobile logout -->
                    <a href="/logout.php" class="btn btn-ghost btn-square btn-sm sm:hidden text-error">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Flash Messages → Toast -->
        <?php
        $flash = App::getFlash();
        if ($flash):
            $toastType = $flash['type'] === 'danger' ? 'alert-error' : ($flash['type'] === 'success' ? 'alert-success' : ($flash['type'] === 'warning' ? 'alert-warning' : 'alert-info'));
            $toastIcon = $flash['type'] === 'danger' ? 'fa-exclamation-circle' : ($flash['type'] === 'success' ? 'fa-check-circle' : ($flash['type'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'));
        ?>
        <div class="toast toast-top toast-end z-[100]" id="flashToast">
            <div class="alert <?= $toastType ?> shadow-lg flex items-center gap-2 pr-6">
                <i class="fas <?= $toastIcon ?>"></i>
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
            </div>
        </div>
        <script>
        setTimeout(function() {
            var t = document.getElementById('flashToast');
            if (t) { t.style.transition = 'opacity 0.5s'; t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 500); }
        }, 4000);
        </script>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 p-4 lg:p-6">
            <?php echo $content; ?>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 mt-auto">
            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; 2026 Inventaris Lab Komputer
            </div>
        </footer>
    </div>

    <!-- ===== SIDEBAR ===== -->
    <div class="drawer-side">
        <label for="sidebar-toggle" class="drawer-overlay"></label>
        <aside class="bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 w-72 min-h-full flex flex-col shadow-lg">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-6 h-16 border-b border-gray-200 dark:border-gray-700">
                <div class="w-9 h-9 bg-primary rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-desktop text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg leading-tight">Inventaris Lab</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Manajemen Aset</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <?php
                $navItems = [
                    '/' => ['label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
                    '/assets/' => ['label' => 'Aset', 'icon' => 'fa-boxes'],
                    '/borrowings/' => ['label' => 'Peminjaman', 'icon' => 'fa-handshake'],
                    '/maintenances/' => ['label' => 'Maintenance', 'icon' => 'fa-tools'],
                    '/reports/' => ['label' => 'Laporan', 'icon' => 'fa-file-alt'],
                ];

                $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

                $activeSection = '/';
                foreach ($navItems as $path => $item) {
                    if (strpos($currentPath, $path) === 0 && $path !== '/') {
                        $activeSection = $path;
                        break;
                    }
                }
                if ($currentPath === '/' || $currentPath === '/index.php') {
                    $activeSection = '/';
                }

                foreach ($navItems as $path => $item):
                    $isActive = $activeSection === $path;
                ?>
                <a href="<?= $path ?>"
                    class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                    <?= $isActive
                        ? 'bg-primary text-primary-content shadow-sm'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-base-200 dark:hover:bg-gray-700' ?>">
                    <i class="fas <?= $item['icon'] ?> w-5 text-center text-sm"></i>
                    <?= $item['label'] ?>
                </a>
                <?php endforeach; ?>

                <!-- Admin Only -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="divider text-xs text-gray-400 my-3 px-4">Admin</div>
                <a href="/users/"
                    class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                    <?= $activeSection === '/users/' ? 'bg-primary text-primary-content shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-base-200 dark:hover:bg-gray-700' ?>">
                    <i class="fas fa-users w-5 text-center text-sm"></i>
                    Pengguna
                </a>
                <a href="/activity/"
                    class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                    <?= $activeSection === '/activity/' ? 'bg-primary text-primary-content shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-base-200 dark:hover:bg-gray-700' ?>">
                    <i class="fas fa-history w-5 text-center text-sm"></i>
                    Activity Log
                </a>
                <?php endif; ?>
            </nav>

            <!-- Bottom -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-3 space-y-1">
                <a href="/profile/"
                    class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-base-200 dark:hover:bg-gray-700 transition-all duration-200">
                    <i class="fas fa-user-circle w-5 text-center"></i>
                    <span><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></span>
                </a>
                <a href="/logout.php"
                    class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-error hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    Logout
                </a>
            </div>
        </aside>
    </div>
</div>

<?php else: ?>
<!-- ===== NO SESSION (Login, Forgot Password) ===== -->
<!-- Flash Messages -->
<?php
$flash = App::getFlash();
if ($flash):
    $type = $flash['type'];
    $message = $flash['message'];
    $alertClass = $type === 'success' ? 'alert-success' : ($type === 'danger' ? 'alert-error' : ($type === 'warning' ? 'alert-warning' : 'alert-info'));
?>
<div class="max-w-md mx-auto mt-4 px-4">
    <div class="alert <?= $alertClass ?> shadow-sm">
        <span><?php echo htmlspecialchars($message); ?></span>
        <button type="button" class="btn btn-ghost btn-xs btn-square" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<?php endif; ?>
<main>
    <?php echo $content; ?>
</main>
<?php endif; ?>

<!-- ===== DELETE CONFIRMATION MODAL ===== -->
<dialog id="deleteModal" class="modal">
    <div class="modal-box">
        <div class="text-center mb-4">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash text-2xl text-red-600"></i>
            </div>
            <h4 class="text-xl font-bold mb-2">Konfirmasi Hapus</h4>
            <p class="text-gray-600 dark:text-gray-400" id="deleteModalMessage">Apakah Anda yakin ingin menghapus data ini?</p>
            <p class="text-sm text-error mt-2" id="deleteModalDetail"></p>
        </div>
        <form method="POST" id="deleteModalForm">
            <?= App::csrfField() ?>
            <input type="hidden" name="delete_id" id="deleteModalId" value="">
            <div class="modal-action">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-error">
                    <i class="fas fa-trash mr-1"></i> Ya, Hapus
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

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

    // Dark mode icon init
    var html = document.documentElement;
    var icon = document.getElementById('darkModeIcon');
    if (icon) {
        icon.className = html.classList.contains('dark') ? 'fas fa-sun' : 'fas fa-moon';
    }

    // Dark mode toggle
    var toggleBtn = document.getElementById('darkModeToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleDarkMode);
    }

    // Global search
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
            closeDeleteModal();
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

// ===== DELETE MODAL =====
function openDeleteModal(actionUrl, deleteId, itemName) {
    document.getElementById('deleteModalForm').action = actionUrl;
    document.getElementById('deleteModalId').value = deleteId;
    document.getElementById('deleteModalMessage').textContent = itemName
        ? 'Apakah Anda yakin ingin menghapus "' + itemName + '"?'
        : 'Apakah Anda yakin ingin menghapus data ini?';
    document.getElementById('deleteModalDetail').textContent = itemName ? '' : 'Tindakan ini tidak dapat dibatalkan.';
    document.getElementById('deleteModal').showModal();
}

function closeDeleteModal() {
    document.getElementById('deleteModal').close();
}
</script>

</body>
</html>
