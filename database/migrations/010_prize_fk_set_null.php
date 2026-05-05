<?php
/**
 * prize_id'yi NULLABLE yap ve FK'yı ON DELETE SET NULL'a çevir.
 * Böylece dilim silindiğinde participants kayıtları korunur.
 *
 * $pdo (PDO) değişkeni runner tarafından inject edilir.
 * Idempotent: birden fazla çalıştırılabilir.
 */

/** @var PDO $pdo */

// 1) prize_id NOT NULL ise NULLABLE yap
$col = $pdo->query("
    SELECT IS_NULLABLE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'prize_id'
    LIMIT 1
")->fetchColumn();

if ($col === 'NO') {
    $pdo->exec("ALTER TABLE participants MODIFY COLUMN prize_id INT UNSIGNED NULL");
}

// 2) Mevcut FK'yı bul ve düşür (auto-generated ad: participants_ibfk_1 vs.)
$fk = $pdo->query("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'prize_id'
      AND REFERENCED_TABLE_NAME = 'prizes'
    LIMIT 1
")->fetchColumn();

// FK'nın delete rule'ını da kontrol et — zaten SET NULL ise dokunma
$needsRebuild = true;
if ($fk) {
    $rule = $pdo->query("
        SELECT DELETE_RULE
        FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND CONSTRAINT_NAME = " . $pdo->quote($fk) . "
        LIMIT 1
    ")->fetchColumn();
    if ($rule === 'SET NULL') {
        $needsRebuild = false;
    }
}

if ($needsRebuild) {
    if ($fk) {
        $pdo->exec("ALTER TABLE participants DROP FOREIGN KEY `" . str_replace('`', '', $fk) . "`");
    }
    $pdo->exec("
        ALTER TABLE participants
          ADD CONSTRAINT participants_prize_fk
          FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE SET NULL
    ");
}
