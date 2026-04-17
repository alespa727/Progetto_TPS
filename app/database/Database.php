<?php
namespace DatabaseUtil;

use PDO;
use Core\Config;

/*
$dsn = "mysql:host=localhost;port=3306;dbname=tps;charset=utf8mb4";
$user = "root";
$pass = "";*/
class Database
{
    public static function getDatabase(): PDO
    {
        $config = Config::get("database");

        $dbname = $config['name'];
        $charset = $config['charset'] ?? 'utf8mb4';

        if (!empty($config['socket'])) {
            $dsn = "mysql:unix_socket=" . $config['socket'] .
                ";dbname=" . $dbname .
                ";charset=" . $charset;
        } else {
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;

            $dsn = "mysql:host=" . $host .
                ";port=" . $port .
                ";dbname=" . $dbname .
                ";charset=" . $charset;
        }

        $user = $config['user'] ?? '';
        $pass = $config['password'] ?? '';

        $db = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]);

        return $db;
    }
}
