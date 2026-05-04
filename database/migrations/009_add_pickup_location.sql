ALTER TABLE prizes
  ADD COLUMN pickup_location VARCHAR(255) DEFAULT NULL AFTER brand_name;

ALTER TABLE participants
  ADD COLUMN pickup_snapshot VARCHAR(255) DEFAULT NULL AFTER brand_snapshot;
