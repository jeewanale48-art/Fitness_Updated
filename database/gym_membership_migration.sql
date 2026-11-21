-- Run once against the FitTrack2 database. Adds gym fee plans and membership payment tracking.
CREATE TABLE IF NOT EXISTS gym_membership_plans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 duration_months INT UNSIGNED NOT NULL,
 fee DECIMAL(10,2) NOT NULL,
 description VARCHAR(500) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS gym_memberships (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 plan_id BIGINT UNSIGNED NOT NULL,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 amount DECIMAL(10,2) NOT NULL,
 payment_status ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending',
 status ENUM('pending','active','expired','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_gym_member_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_gym_member_plan FOREIGN KEY (plan_id) REFERENCES gym_membership_plans(id) ON DELETE RESTRICT,
 INDEX idx_gym_member_status (user_id,status,end_date)
) ENGINE=InnoDB;
