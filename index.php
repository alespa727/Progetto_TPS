<?php

use Core\FileHandler;

$start = microtime(true);
use Core\Router;

require __DIR__ . '/vendor/autoload.php';

Router::loadConfig(["routes" => __DIR__ . "/routes", "middlewares" => __DIR__ . "/middlewares", "debug" => true]);
Router::init();

FileHandler::setStaticFilesPath(__DIR__ . "/static");

$allowedHosts = [
    "localhost",
];

Router::handleDirect($allowedHosts, $start);
