<?php
require_once __DIR__ . '/core/Database.php';
$db = Database::getInstance()->getConnection();

$sql = "
CREATE TABLE IF NOT EXISTS asset_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asset_id (asset_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($db->query($sql)) {
    echo "SUCCESS: asset_logs table created.\n";
} else {
    echo "ERROR asset_logs: " . $db->error . "\n";
}

$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','lab_assistant','guru','siswa','viewer','operator','user') DEFAULT 'user'";
if ($db->query($sql)) {
    echo "SUCCESS: ENUM expanded with viewer, operator, user (temporary).\n";
} else {
    echo "ERROR ENUM expand: " . $db->error . "\n";
}

$db->query("UPDATE users SET role = 'viewer' WHERE role = 'guru'");
$db->query("UPDATE users SET role = 'user' WHERE role = 'siswa'");
$affected = $db->affected_rows;
echo "INFO: Existing roles migrated (guru->viewer, siswa->user).\n";

$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','lab_assistant','viewer','operator') DEFAULT 'user'";
if ($db->query($sql)) {
    echo "SUCCESS: role ENUM cleaned up (removed guru, siswa).\n";
} else {
    echo "ERROR ENUM cleanup: " . $db->error . "\n";
}

$sql = "CREATE TABLE IF NOT EXISTS permissions (
    user_id INT,
    permission VARCHAR(50),
    PRIMARY KEY (user_id, permission),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if ($db->query($sql)) {
    echo "SUCCESS: permissions table created.\n";
} else {
    echo "ERROR permissions: " . $db->error . "\n";
}

$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_name VARCHAR(100) DEFAULT '',
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT DEFAULT NULL,
    description TEXT,
    ip_address VARCHAR(45) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_table (table_name),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if ($db->query($sql)) {
    echo "SUCCESS: activity_logs table created.\n";
} else {
    echo "ERROR activity_logs: " . $db->error . "\n";
}

echo "\n✅ Migrasi selesai. Silakan login dengan username: admin | password: admin123\n";
