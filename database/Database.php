<?php
namespace DatabaseUtil;

use PDO;


/*
$dsn = "mysql:host=localhost;port=3306;dbname=tps;charset=utf8mb4";
$user = "root";
$pass = "";*/
class Database
{
    public static function getDatabase(): PDO
    {
        $dsn = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=tps;charset=utf8mb4";
        $user = "root";
        $pass = "";
        $db = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        return $db;
    }
}
