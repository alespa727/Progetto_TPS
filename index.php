<?php

use Core\Config;
error_reporting(E_ERROR);

define('BASE_PATH', __DIR__);
$start = microtime(true);

use Core\FileHandler;
use Core\Router;

require __DIR__ . '/vendor/autoload.php';

Config::load(__DIR__."/config/config.yaml");
Router::init();

FileHandler::setStaticFilesPath(Config::path("directories.static"));
Router::handle($start);
