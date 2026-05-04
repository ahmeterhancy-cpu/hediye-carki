CREATE TABLE IF NOT EXISTS participants (
  id                  INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  first_name          VARCHAR(80) NOT NULL,
  last_name           VARCHAR(80) NOT NULL,
  phone               VARCHAR(20) NOT NULL,
  receipt_no          VARCHAR(50),
  receipt_amount      DECIMAL(10,2),
  prize_id            INT UNSIGNED NOT NULL,
  prize_name_snapshot VARCHAR(150) NOT NULL,
  brand_snapshot      VARCHAR(100),
  staff_id            INT UNSIGNED,
  ip_address          VARCHAR(45),
  user_agent          VARCHAR(255),
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_phone_date (phone, created_at),
  INDEX idx_created (created_at),
  FOREIGN KEY (prize_id) REFERENCES prizes(id),
  FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
