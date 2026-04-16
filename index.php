<?php

use Core\Config;

define('BASE_PATH', __DIR__);
$start = microtime(true);

use Core\FileHandler;
use Core\Router;

require __DIR__ . '/vendor/autoload.php';

register_shutdown_function(function () {
    $error = error_get_last();
    if($error){
        if($error["file"]==="/home/ale/htdocs/vendor/zircote/swagger-php/src/Annotations/AbstractAnnotation.php"){
            exit;
        }
        echo json_encode([
            "errore" => $error["message"],
            "file"   => $error["file"],
            "linea"  => $error["line"]
        ]);
    }
    
});

Config::load(__DIR__."/config/config.yaml");
Router::init();

FileHandler::setStaticFilesPath(Config::path("directories.static"));
Router::handleDirect($start);
