<?php

$dsn = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=progetto_tps;charset=utf8mb4";
$user = "db_user";
$pass = "db_pass";

$db = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

return $db;