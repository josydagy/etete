-- Import this file in phpMyAdmin after selecting supermarket_db.
-- It configures application roles and creates the Storekeeper stock history table. The legacy supplier enum value is retained only to avoid damaging existing database records.
ALTER TABLE users MODIFY role ENUM('admin','customer','storekeeper','supplier') NOT NULL DEFAULT 'customer';
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS stock_history (
 id INT AUTO_INCREMENT PRIMARY KEY,
 product_id INT NOT NULL,
 user_id INT NOT NULL,
 action ENUM('in','out') NOT NULL,
 quantity INT NOT NULL,
 previous_quantity INT NOT NULL,
 new_quantity INT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX(product_id), INDEX(user_id), INDEX(created_at),
 CONSTRAINT fk_stock_history_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_stock_history_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


