<?php
/**
 * Database Configuration Example
 *
 * Copy this file to database.php and update the values,
 * OR set the corresponding environment variables in your hosting dashboard.
 *
 * ===== Railway Users =====
 * Set these as environment variables in your Railway project dashboard:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_URL, APP_ENV, APP_DEBUG
 * Railway auto-injects MYSQL_* env vars if you attach a MySQL plugin.
 *
 * ===== Local Development =====
 * Copy to database.php and uncomment/update the values below.
 */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pixelpod_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/PixelPodWeb');
define('SITE_NAME', getenv('SITE_NAME') ?: 'Pixel Pod Photobooth');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1');
