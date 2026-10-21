-- Practical 14: general audit trail for administrator actions.
CREATE TABLE IF NOT EXISTS audit_logs (
 audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 actor_user_id INT UNSIGNED NULL,
 action VARCHAR(40) NOT NULL,
 target_type VARCHAR(40) NOT NULL,
 target_id INT UNSIGNED NULL,
 summary VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_audit_created (created_at,audit_id),
 KEY idx_audit_actor (actor_user_id),
 CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
