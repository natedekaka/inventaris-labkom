# Inventaris Lab Komputer

Aplikasi web manajemen inventaris laboratorium komputer berbasis PHP native, MySQL, dan Tailwind CSS.

## Fitur

### Manajemen Aset
- CRUD aset (tambah, edit, detail, hapus) dengan upload foto
- Kode aset otomatis (INV-001, INV-002, ...)
- Kategori & lokasi aset
- Kondisi (baik, rusak ringan, rusak berat)
- Status (tersedia, dipinjam, perbaikan)
- Import CSV massal
- QR Code (generate per aset, batch, scan)

### Peminjaman
- Form peminjaman dengan approval workflow (pending → approve/tolak → dipinjam → dikembalikan)
- Filter status, pencarian, filter tanggal
- Notifikasi overdue dengan severity (info/warning/critical)
- Perhitungan denda otomatis (Rp 1.000/hari keterlambatan)

### Maintenance
- Catat maintenance rutin & perbaikan
- Update status aset otomatis (perbaikan ↔ tersedia)
- Reminder jadwal maintenance di dashboard

### Pengguna & Keamanan
- 5 level role: admin, lab_assistant, operator, user, viewer
- CSRF protection
- Forgot/Reset Password (token-based)
- Ganti password dari halaman profil
- Session management

### Laporan
- Export CSV & cetak PDF (aset, peminjaman, maintenance)
- Filter laporan per periode
- Statistik dashboard dengan Chart.js

### Lainnya
- Dark mode
- Global search (real-time)
- Notifikasi overdue di navbar
- Notifikasi maintenance terjadwal
- Audit log timeline di detail aset
- Backup database (mysqldump)
- Responsive mobile & desktop

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 8.2 native (tanpa framework) |
| Database | MariaDB 10.11 / MySQL 8 |
| Frontend | Tailwind CSS (CDN), Font Awesome |
| Charts | Chart.js 4.4 |
| QR Scanner | html5-qrcode |
| Container | Docker / Podman |

## Cara Install

### Prasyarat
- Docker / Podman (direkomendasikan)
- Atau: PHP 8.0+, MySQL/MariaDB, web server

### Via Docker

```bash
cd inventaris-labkom
docker compose up -d
```

Akses:
| Service | URL |
|---------|-----|
| Aplikasi | http://localhost:8088 |
| phpMyAdmin | http://localhost:8089 |

Login default: `admin` / `admin123`

### Via Podman

```bash
cd inventaris-labkom
podman-compose up -d
```

### Manual (XAMPP / LAMP)

1. Copy folder ke `htdocs` atau `www`
2. Buat database: `inventaris_labkom`
3. Import `db/schema.sql`
4. Edit koneksi di `config/database.php`
5. Jalankan `run_migration.php` dari browser
6. Akses: http://localhost/inventaris-labkom

## Role & Hak Akses

| Role | Aset | Peminjaman | Maintenance | Users | Laporan |
|------|------|------------|-------------|-------|---------|
| admin | CRUD | Approve/kembalikan | CRUD | CRUD | Lihat |
| lab_assistant | CRUD | Approve/kembalikan | CRUD | - | Lihat |
| operator | - | Buat/kembalikan | - | - | Lihat |
| user | Lihat | Buat sendiri | - | - | - |
| viewer | Lihat | - | - | - | Lihat |

## Struktur Folder

```
inventaris-labkom/
├── assets/          # Manajemen aset (CRUD, import, QR)
├── borrowings/      # Peminjaman & pengembalian
├── maintenances/    # Maintenance aset
├── users/           # Manajemen pengguna (admin only)
├── reports/         # Laporan (export CSV, print PDF)
├── profile/         # Profil & ganti password
├── dashboard/       # Fungsi statistik
├── config/          # Konfigurasi database & sekolah
├── core/            # Database, App (auth, csrf), helpers
├── db/              # Schema SQL & migrasi
├── views/           # Layout & sidebar
├── uploads/         # Upload foto aset
│
├── login.php        # Halaman login
├── forgot_password.php    # Lupa password
├── reset_password.php     # Reset password
├── index.php        # Dashboard
├── scan.php         # Scan QR code
├── search.php       # Global search API
├── notifications.php      # Notifikasi overdue API
├── backup.php       # Backup database (admin)
├── docker-compose.yml
└── README.md
```

## API Endpoints

| Endpoint | Method | Fungsi |
|----------|--------|--------|
| `search.php?q=...` | GET | Pencarian global aset & user |
| `notifications.php` | GET | Data notifikasi overdue |
| `overdue_count.php` | GET | Jumlah overdue (badge) |
| `assets/check_kode.php?kode=...` | GET | Cek aset by kode |
| `assets/check_id.php?id=...` | GET | Detail aset by ID |
| `assets/cek_duplikat_kode.php?kode=...` | GET | Cek ketersediaan kode |
| `assets/generate_kode.php` | GET | Generate kode aset baru |
| `assets/tambah_kategori.php` | POST | Tambah kategori (AJAX) |
| `assets/tambah_lokasi.php` | POST | Tambah lokasi (AJAX) |
