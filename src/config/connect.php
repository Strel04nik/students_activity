<?php

namespace db;

use PDO, PDOException;

class database
{
    // *********
    // То, что нужно менять!
    public static $host = '127.0.0.1:3306';
    public static $dbname = 'students_activity_new';
    public static $username = 'root';
    public static $password = '';
    // ********

    public static $pdo = null;

    public static function connect()
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO("mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4", self::$username, self::$password);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Ошибка подключения: " . $e->getMessage());
            }
        }
    }
}
