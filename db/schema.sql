DROP TABLE IF EXISTS maintenances;
DROP TABLE IF EXISTS borrowings;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_lokasi VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_aset VARCHAR(50) UNIQUE NOT NULL,
    nama_barang VARCHAR(100) NOT NULL,
    merek VARCHAR(50),
    model VARCHAR(100),
    serial_number VARCHAR(100) UNIQUE,
    spesifikasi TEXT,
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat') DEFAULT 'baik',
    status ENUM('tersedia', 'dipinjam', 'perbaikan') DEFAULT 'tersedia',
    category_id INT,
    location_id INT,
    tanggal_beli DATE,
    harga DECIMAL(12,2),
    garansi_sampai DATE,
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_kondisi (kondisi),
    INDEX idx_kode (kode_aset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nis VARCHAR(20) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin', 'lab_assistant', 'guru', 'siswa') DEFAULT 'siswa',
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE borrowings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    asset_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    rencana_kembali DATE NOT NULL,
    tanggal_kembali DATE,
    keperluan TEXT,
    status ENUM('dipinjam', 'dikembalikan', 'terlambat') DEFAULT 'dipinjam',
    denda DECIMAL(10,2) DEFAULT 0,
    kondisi_saat_kembali ENUM('baik', 'rusak_ringan', 'rusak_berat'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE maintenances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    tanggal_maintenance DATE NOT NULL,
    jenis ENUM('rutin', 'perbaikan') NOT NULL,
    deskripsi TEXT,
    teknisi VARCHAR(100),
    biaya DECIMAL(12,2),
    status ENUM('selesai', 'belum') DEFAULT 'belum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (nama_kategori) VALUES ('PC Desktop'), ('Laptop'), ('Periferal'), ('Networking'), ('Proyektor'), ('Lainnya');
INSERT INTO locations (nama_lokasi) VALUES ('Lab 1'), ('Lab 2'), ('Ruang Server'), ('Gudang');
