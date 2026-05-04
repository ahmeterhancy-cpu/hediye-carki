CREATE TABLE IF NOT EXISTS audit_logs (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     INT UNSIGNED,
  action      VARCHAR(50) NOT NULL,
  entity_type VARCHAR(50),
  entity_id   INT UNSIGNED,
  payload     JSON,
  ip_address  VARCHAR(45),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_time (user_id, created_at),
  INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
