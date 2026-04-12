<?php
// File: api/config/constants.php
 
// Duong dan thu muc upload (tuyet doi)
define('UPLOAD_DIR_ORIGINAL',  '/var/www/videohub/uploads/original/');
define('UPLOAD_DIR_PROCESSED', '/var/www/videohub/uploads/processed/');
define('UPLOAD_DIR_THUMBS',    '/var/www/videohub/uploads/thumbnails/');
 
// Gioi han kich thuoc file: 500MB
define('MAX_FILE_SIZE', 500 * 1024 * 1024);
 
// Dinh dang cho phep (kiem tra MIME type thuc su)
define('ALLOWED_MIME', [
    'video/mp4', 'video/avi', 'video/x-msvideo',
    'video/quicktime', 'video/x-matroska', 'video/webm',
]);
 
// Duong dan den FFmpeg
define('FFMPEG',  '/usr/bin/ffmpeg');
define('FFPROBE', '/usr/bin/ffprobe');
 
// Tat cache tren moi trinh duyet khi phat trien
define('DEV_MODE', true);
?>
