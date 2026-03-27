<?php
ini_set('display_errors', 1);      // Mostra gli errori
ini_set('display_startup_errors', 1); // Mostra errori di startup
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
include_once "functions.php";
require "autoloader.php";


$allowedHosts = [
    "localhost",
];

$request = new Request();
/*
$routes = require "routes.php";
Router::handle($request, $routes, $allowedHosts);
*/
$indexedRoutes = [];

if (didRouteFileChange()) {
    echo "File cambiato\n";
    $indexedRoutes = (require "build_routes.php")[$request->getSegments()[0]];
} else {
    if (!empty($request->getSegments()) && file_exists("cache/routes_" . $request->getSegments()[0] . ".php"))
        $indexedRoutes = require "cache/routes_" . $request->getSegments()[0] . ".php";
}



// Gestione della richiesta automatica
Router::handle2($request, $indexedRoutes, $allowedHosts);
//Router::handle($request, $routes, $allowedHosts);
