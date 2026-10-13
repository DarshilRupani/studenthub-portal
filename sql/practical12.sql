-- Practical 12: additive event management migration.
ALTER TABLE events ADD COLUMN IF NOT EXISTS category ENUM('technical','academic','cultural','sports','other') NOT NULL DEFAULT 'technical';
ALTER TABLE events ADD COLUMN IF NOT EXISTS status ENUM('open','closed') NOT NULL DEFAULT 'open';
ALTER TABLE events ADD COLUMN IF NOT EXISTS poster_filename VARCHAR(40) NULL;
CREATE TABLE IF NOT EXISTS event_audit (
 audit_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id INT UNSIGNED NULL,
 event_title VARCHAR(150) NOT NULL,
 actor_user_id INT UNSIGNED NULL,
 action ENUM('added','updated','deleted') NOT NULL,
 before_data TEXT NULL,
 after_data TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL,
 FOREIGN KEY (actor_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO events (title,description,event_date,venue,category,status)
SELECT 'Campus Sports Day','Sample event for the management practical.','2026-11-05 09:00:00','College Ground','sports','open'
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title='Campus Sports Day');
INSERT INTO events (title,description,event_date,venue,category,status)
SELECT 'Cultural Evening','Sample closed event for category and status filtering.','2026-11-07 17:00:00','College Auditorium','cultural','closed'
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title='Cultural Evening');
