-- Migration: Add password reset fields to users table
ALTER TABLE users 
ADD COLUMN reset_token VARCHAR(64) NULL AFTER password,
ADD COLUMN reset_expires DATETIME NULL AFTER reset_token,
ADD INDEX idx_reset_token (reset_token);
