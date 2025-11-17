<?php
namespace App;

use PDO;

class Db {
    private static $pdo;

    public static function getConnection($settings) {
        if (self::$pdo) return self::$pdo;
        
        // Try SQLite first (local fallback), then MySQL
        $dbPath = __DIR__ . '/../chat.db';
        if (file_exists($dbPath)) {
            // Use SQLite if DB file exists locally
            $dsn = 'sqlite:' . $dbPath;
            self::$pdo = new PDO($dsn, '', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } else {
            // Try MySQL
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $settings['db']['host'], $settings['db']['port'], $settings['db']['dbname']);
            self::$pdo = new PDO($dsn, $settings['db']['user'], $settings['db']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        return self::$pdo;
    }
}
