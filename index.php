<?php
$start = microtime(true);
use Core\Router;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/vendor/autoload.php';

Router::loadConfig(["routes"=>__DIR__."/routes", "middlewares"=>__DIR__."/middlewares"]);
Router::init();

$allowedHosts = [
    "localhost",
];

Router::handleDirect($allowedHosts, $start);
