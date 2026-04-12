-- File: database/schema.sql
USE videodb;
 
-- Bang nguoi dung
CREATE TABLE IF NOT EXISTS users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50) NOT NULL UNIQUE,
  email       VARCHAR(100) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Bang video chinh
CREATE TABLE IF NOT EXISTS videos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL DEFAULT 1,
  title           VARCHAR(255) NOT NULL,
  description     TEXT,
  original_path   VARCHAR(500),
  processed_path  VARCHAR(500),
  thumbnail_path  VARCHAR(500),
  duration        INT DEFAULT 0,
  file_size       BIGINT DEFAULT 0,
  status          ENUM('uploading','processing','ready','error') DEFAULT 'uploading',
  views           INT DEFAULT 0,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Bang ghi log upload (do luong do tre)
CREATE TABLE IF NOT EXISTS upload_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  video_id    INT,
  start_time  DATETIME,
  end_time    DATETIME,
  file_size   BIGINT,
  status      VARCHAR(50),
  error_msg   TEXT,
  ip_address  VARCHAR(45),
  INDEX idx_video_id (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Them user demo (mat khau: demo123)
INSERT IGNORE INTO users (username, email, password)
VALUES ('demo', 'demo@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
