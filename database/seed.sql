-- Default admin: admin@local / admin123
INSERT IGNORE INTO users (name, email, password_hash, role, is_active)
VALUES ('Admin', 'admin@local', '$2y$10$TTGsmJ4RMb.TQ2PE..t81ePIEOFHffTsy5i/aSoMaNg3nWUkuL4n2', 'admin', 1);

-- Settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('min_spend',        '500'),
  ('start_time',       '10:00'),
  ('end_time',         '22:00'),
  ('per_phone_limit',  '1'),
  ('per_phone_window', '1'),
  ('event_active',     '1'),
  ('event_title',      'Mandella Açılış Çekilişi'),
  ('code_ttl_seconds', '300');

-- 8 demo dilim
INSERT IGNORE INTO prizes (id, name, brand_name, color_hex, weight, display_order, is_active) VALUES
  (1, 'Kahve Çeki',      'Starbucks',  '#00704A', 20, 1, 1),
  (2, 'Yemek Kuponu',    'Burger King','#D62300', 15, 2, 1),
  (3, '%10 İndirim',     'Zara',       '#000000', 25, 3, 1),
  (4, 'Sinema Bileti',   'Cinemaximum','#E31837', 10, 4, 1),
  (5, 'Parfüm Seti',     'Sephora',    '#000000', 8,  5, 1),
  (6, 'Kitap Kuponu',    'D&R',        '#FF6600', 12, 6, 1),
  (7, 'Teknoloji Seti',  'MediaMarkt', '#CC0000', 5,  7, 1),
  (8, 'Sürpriz Hediye',  'AVM',        '#FFB400', 5,  8, 1);

-- Demo stokları
INSERT IGNORE INTO stocks (prize_id, initial_qty, remaining_qty, daily_limit) VALUES
  (1, 100, 100, 20),
  (2, 80,  80,  15),
  (3, 200, 200, NULL),
  (4, 50,  50,  10),
  (5, 30,  30,  5),
  (6, 60,  60,  NULL),
  (7, 10,  10,  2),
  (8, 50,  50,  NULL);
