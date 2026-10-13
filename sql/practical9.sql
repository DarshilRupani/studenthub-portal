-- Run in the existing studenthub_db database. Practical 8 tables are preserved.
CREATE TABLE IF NOT EXISTS users (
 user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fullname VARCHAR(50) NOT NULL,
 email VARCHAR(150) NOT NULL,
 mobile VARCHAR(10) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 course VARCHAR(30) NOT NULL,
 year_of_study TINYINT UNSIGNED NOT NULL CHECK (year_of_study BETWEEN 1 AND 4),
 gender ENUM('male','female','other') NOT NULL,
 role ENUM('student','faculty') NOT NULL DEFAULT 'student',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS registration_audit (
 audit_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 action VARCHAR(40) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
