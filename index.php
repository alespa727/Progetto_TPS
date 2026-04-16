<?php


use Core\FileHandler;

$start = microtime(true);
use Core\Router;
use Core\Response;
use Core\ContentTypes;

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

Router::loadConfig(["routes" => __DIR__ . "/routes", "middlewares" => __DIR__ . "/middlewares", "debug" => false]);
Router::init();


FileHandler::setStaticFilesPath(__DIR__ . "/static");

$allowedHosts = [
    "localhost",
];

Router::handleDirect($allowedHosts, $start);
