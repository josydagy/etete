-- FreshMart database repair / upgrade script
-- Select supermarket_db in phpMyAdmin before importing this file.
-- This script preserves existing records. It only adds missing columns/tables.

-- Keep the legacy supplier enum value to preserve existing database accounts; the Supplier module is removed from this project ZIP.
ALTER TABLE users
  MODIFY role ENUM('admin','customer','storekeeper','supplier') NOT NULL DEFAULT 'customer';

-- Add users.is_active only when it is missing (works with MySQL/MariaDB).
SET @has_is_active = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_active'
);
SET @sql_is_active = IF(@has_is_active = 0,
  'ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT ''users.is_active already exists'' AS info'
);
PREPARE stmt_is_active FROM @sql_is_active;
EXECUTE stmt_is_active;
DEALLOCATE PREPARE stmt_is_active;

-- Add users.profile_photo only when it is missing.
SET @has_profile_photo = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_photo'
);
SET @sql_profile_photo = IF(@has_profile_photo = 0,
  'ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL',
  'SELECT ''users.profile_photo already exists'' AS info'
);
PREPARE stmt_profile_photo FROM @sql_profile_photo;
EXECUTE stmt_profile_photo;
DEALLOCATE PREPARE stmt_profile_photo;

-- Stock movement audit trail.
CREATE TABLE IF NOT EXISTS stock_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  action ENUM('in','out') NOT NULL,
  quantity INT NOT NULL,
  previous_quantity INT NOT NULL,
  new_quantity INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_stock_history_product (product_id),
  INDEX idx_stock_history_user (user_id),
  INDEX idx_stock_history_created (created_at),
  CONSTRAINT fk_stock_history_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_stock_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'FreshMart database repair completed. Check that stock_history exists.' AS result;
