<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'pixelpod_db');
define('DB_USER',    'root');
define('DB_PASS',    ''); 
define('DB_CHARSET', 'utf8mb4');

// Site URL
define('SITE_URL',  'http://localhost/PixelPodWeb');
define('SITE_NAME', 'Pixel Pod Photobooth');

// DataBase Connection
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn     = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('
            <div style="font-family:sans-serif;padding:40px;background:#fff0f0;border:2px solid red;margin:40px;border-radius:8px;">
                <h2>&#9888; Database Connection Failed</h2>
                <p>Please check:</p>
                <ol>
                    <li>XAMPP is running — Apache + MySQL both <strong>green</strong></li>
                    <li>You imported <strong>database/pixelpod.sql</strong> in phpMyAdmin</li>
                    <li>Database name is <strong>pixelpod_db</strong></li>
                    <li><strong>config/database.php</strong> has the right DB_USER / DB_PASS</li>
                </ol>
                <p style="color:red">Error: ' . $e->getMessage() . '</p>
            </div>');
        }
    }
    return $pdo;
}
