-- Optional: the app creates this table automatically on first use (includes/videos.php).
CREATE TABLE IF NOT EXISTS premium_videos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  youtube_id VARCHAR(20) NOT NULL,
  category ENUM('workout','diet','general') NOT NULL DEFAULT 'workout',
  fitness_level ENUM('all','beginner','intermediate','expert') NOT NULL DEFAULT 'all',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
