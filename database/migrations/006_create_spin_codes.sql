CREATE TABLE IF NOT EXISTS spin_codes (
  id             INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code           CHAR(6) NOT NULL,
  staff_id       INT UNSIGNED NOT NULL,
  receipt_no     VARCHAR(50),
  receipt_amount DECIMAL(10,2),
  status         ENUM('pending','consumed','expired','cancelled') DEFAULT 'pending',
  consumed_at    TIMESTAMP NULL,
  participant_id INT UNSIGNED NULL,
  expires_at     TIMESTAMP NOT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_active_code (code, status),
  INDEX idx_status_expires (status, expires_at),
  FOREIGN KEY (staff_id) REFERENCES users(id),
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
