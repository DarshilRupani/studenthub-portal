-- Import after Practical 9. MariaDB supports ADD COLUMN IF NOT EXISTS.
ALTER TABLE users MODIFY role ENUM('student','faculty','admin') NOT NULL DEFAULT 'student';
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL;
CREATE TABLE IF NOT EXISTS remember_tokens (
 selector CHAR(24) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 expires_at DATETIME NOT NULL,
 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
