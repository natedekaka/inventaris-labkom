-- Migration: Add asset_logs table for unified history timeline
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
