-- database/schema.sql
USE videodb;

/* ==========================================================
   TABLE: videos (Lưu metadata gốc)
   ========================================================== */
CREATE TABLE IF NOT EXISTS videos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    description    TEXT,
    original_path  VARCHAR(500),
    processed_path VARCHAR(500),
    thumb_path     VARCHAR(500),
    duration       INT DEFAULT 0,
    native_height  INT DEFAULT 0,
    native_width   INT DEFAULT 0,
    file_size      BIGINT DEFAULT 0,
    status         ENUM('uploading','processing','ready','error') DEFAULT 'uploading',
    views          INT DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ==========================================================
   TABLE: upload_logs (Đo lường độ trễ - Latency)
   ========================================================== */
CREATE TABLE IF NOT EXISTS upload_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    video_id   INT,
    start_time DATETIME,
    end_time   DATETIME,
    file_size  BIGINT,
    status     VARCHAR(50),
    error_msg  TEXT,
    ip         VARCHAR(45),
    
    INDEX idx_vid (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ==========================================================
   TABLE: video_renditions (Quản lý các bản Transcode)
   ========================================================== */
CREATE TABLE IF NOT EXISTS video_renditions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    video_id   INT NOT NULL,
    label      VARCHAR(10) NOT NULL,
    height     INT NOT NULL,
    width      INT NOT NULL,
    file_path  VARCHAR(500) NOT NULL,
    file_size  BIGINT DEFAULT 0,
    bitrate    INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_vid (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
