<?php
// ============================================================
// Zbox Asist — DB Bağlantısı
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'zbasist');
define('DB_USER', 'zbasist_usr');
define('DB_PASS', '1*BuHU5th$vp7qzr');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Production'da detay gösterme
            error_log('DB bağlantı hatası: ' . $e->getMessage());
            die('Veritabanı bağlantısı kurulamadı.');
        }
    }

    return $pdo;
}
