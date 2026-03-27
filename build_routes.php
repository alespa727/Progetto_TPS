<?php
use Symfony\Component\VarExporter\VarExporter;

ini_set('display_errors', 1);      // Mostra gli errori
ini_set('display_startup_errors', 1); // Mostra errori di startup
error_reporting(E_ALL);
include_once "functions.php";
require "autoloader.php";
$routes = require "routes.php";
$indexedRoutes = [];

foreach ($routes as $route) {
    $node = &$indexedRoutes;

    $pattern = $route->getPattern();
    $lastIndex = count($pattern) - 1;
    

    foreach ($pattern as $i => $segment) {
        if (!isset($node[$segment])) {
            $node[$segment] = [];
        }

        if(!isset($node[$segment]['methods'])){
            $node[$segment]['methods'] = [];
        }
        

        if ($segment[0] === "{" && $segment[strlen($segment) - 1] === '}') {
            $node['_param'] = $segment;   
        }


        if ($i === $lastIndex) {
            $node[$segment]['_' . $route->getMethod()] = $route->toArray();
           $node[$segment]['methods'][]=$route->getMethod();
        }

        $node = &$node[$segment];
    }
}

if (!is_dir(__DIR__ . '/cache')) {
    mkdir(__DIR__ . '/cache', 0777, true);
}

foreach ($indexedRoutes as $prefix => $routes) {
    $path = __DIR__ . '/cache/routes_' . $prefix . '.php';

    
    $data = "<?php\nreturn " . VarExporter::export($routes) . ";\n";
    file_put_contents($path, $data);
}

return $indexedRoutes;