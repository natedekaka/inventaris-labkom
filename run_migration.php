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
    echo "ERROR: " . $db->error . "\n";
}
