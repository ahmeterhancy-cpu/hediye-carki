CREATE TABLE IF NOT EXISTS rate_limits (
  cache_key  VARCHAR(120) PRIMARY KEY,
  attempts   INT UNSIGNED DEFAULT 1,
  expires_at TIMESTAMP NOT NULL,
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
