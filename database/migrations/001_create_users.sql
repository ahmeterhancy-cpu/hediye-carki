CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) UNIQUE NULL,
  password_hash VARCHAR(255) NULL,
  pin_hash      VARCHAR(255) NULL,
  role          ENUM('admin','staff') NOT NULL,
  is_active     TINYINT(1) DEFAULT 1,
  created_by    INT UNSIGNED NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
