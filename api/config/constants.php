<?php
/* ==========================================================================
   SYSTEM CONSTANTS
   ========================================================================== */
define('ROOT', dirname(__DIR__, 2));

define('DIR_ORIG',  ROOT . '/uploads/original/');
define('DIR_PROC',  ROOT . '/uploads/processed/');
define('DIR_THUMB', ROOT . '/uploads/thumbs/');

define('MAX_BYTES', 500 * 1024 * 1024); // Giới hạn 500MB

define('ALLOWED_MIME', [
    'video/mp4', 'video/avi', 'video/x-msvideo',
    'video/quicktime', 'video/x-matroska', 'video/webm'
]);

// Đường dẫn binary của FFmpeg
define('FFMPEG',  '/usr/bin/ffmpeg');
define('FFPROBE', '/usr/bin/ffprobe');
