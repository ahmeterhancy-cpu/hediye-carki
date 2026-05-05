-- prize_id'yi NULLABLE yap ve FK'yı ON DELETE SET NULL'a çevir
-- Böylece dilim silindiğinde participants kayıtları korunur (snapshot kolonları zaten dolu).

-- 1) prize_id NOT NULL ise NULLABLE yap (idempotent — zaten NULLABLE ise no-op)
ALTER TABLE participants MODIFY COLUMN prize_id INT UNSIGNED NULL;

-- 2) Mevcut FK'yı bul ve düşür (auto-generated ad: participants_ibfk_1 vs.)
SET @fk := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'participants'
    AND COLUMN_NAME = 'prize_id'
    AND REFERENCED_TABLE_NAME = 'prizes'
  LIMIT 1
);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE participants DROP FOREIGN KEY `', @fk, '`'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Yeni FK'yı ekle (ON DELETE SET NULL)
ALTER TABLE participants
  ADD CONSTRAINT participants_prize_fk
  FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE SET NULL;
