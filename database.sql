CREATE DATABASE IF NOT EXISTS cflow CHARACTER SET utf8mb4;
USE cflow;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  google_id VARCHAR(64) UNIQUE, name VARCHAR(100), email VARCHAR(150) UNIQUE, avatar VARCHAR(255),
  savings_goal BIGINT DEFAULT 10000000, daily_limit BIGINT DEFAULT 0, password_hash VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(60), type ENUM('income','expense'),
  bucket ENUM('needs','wants','savings') NULL, icon VARCHAR(8)
);
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, category_id INT NOT NULL,
  type ENUM('income','expense') NOT NULL, amount BIGINT NOT NULL,
  title VARCHAR(150) NOT NULL, method VARCHAR(20) DEFAULT 'Cash', note VARCHAR(500), tags VARCHAR(120),
  receipt VARCHAR(120) NULL, featured TINYINT(1) NOT NULL DEFAULT 0,
  trx_date DATETIME NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, trx_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
CREATE TABLE wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, name VARCHAR(100), price BIGINT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE category_budgets (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, category_id INT NOT NULL,
  budget_month CHAR(7) NOT NULL, amount BIGINT NOT NULL,
  UNIQUE KEY user_category_month (user_id, category_id, budget_month),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
INSERT INTO categories (name,type,bucket,icon) VALUES
('Gaji & Income','income',NULL,'💼'),('Freelance','income',NULL,'💻'),('Bonus & Lainnya','income',NULL,'🎁'),
('Makan & Minuman','expense','needs','🍜'),('Transport','expense','needs','🛵'),('Tagihan & Kos','expense','needs','🏠'),
('Langganan & Subscriptions','expense','wants','📺'),('Lifestyle & Hangout','expense','wants','☕'),('Belanja','expense','wants','🛍️'),
('Investasi & Crypto','expense','savings','📈'),('Tabungan','expense','savings','🐖');
