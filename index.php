<?php
$start = microtime(true);
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/
error_reporting(E_ERROR);
require __DIR__ . '/vendor/autoload.php';
include_once "functions.php";
require "autoloader.php";

$allowedHosts = [
    "localhost",
];

$request = new Request();

$method = RouterMethod::Direct;

switch ($method) {
    case RouterMethod::Direct:
        $indexedRoutes = [];

        if (routesHaveChanged()/*didRouteFileChange()*/) {
            $indexedRoutes = (require "build_routes.php")($request);
        } else {
            if (!empty($request->getSegments()) && file_exists("cache/routes_" . $request->getSegments()[0] . ".php"))
                $indexedRoutes = require "cache/routes_" . $request->getSegments()[0] . ".php";
        }

        Router::handleDirect($request, $indexedRoutes, $allowedHosts, $start);
        break;

    default:
        
        break;
}
