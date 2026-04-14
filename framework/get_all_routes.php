<?php
namespace Core;
use Symfony\Component\VarExporter\VarExporter;


return function (string $path, Request $request = new Request()): array|null {

    include_once "functions.php";

    $before = get_declared_classes();
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path)
    );

    try {

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getPathname();
            }
        }

    } catch (\Throwable $th) {
        $res = Response::new()
                ->internalServerError()
                ->body(["description"=>"route duplicate"]);
        Router::sendResponse($res, ContentTypes::Json);
    }

    $after = get_declared_classes();
    $newClasses = array_diff($after, $before);

    $routes = [];

    foreach ($newClasses as $className) {
        $reflection = new \ReflectionClass($className);
        $attributes = $reflection->getAttributes(Route::class);
        $apiDocAttrs = $reflection->getAttributes(ApiDoc::class);

        foreach ($attributes as $attr) {
            /** 
             * @var Route $route
             */
            $route = $attr->newInstance();

            $routeArray = $route->toArray();
            $controllerPath = $reflection->getFileName();
            $routeArray["controller"] = $className;
            $routeArray["controller_path"] = $controllerPath;
            $routeArray["docs"] = !empty($apiDocAttrs)
                ? $apiDocAttrs[0]->newInstance()
                : null;
            $routes[] = Route::fromArray($routeArray);

        }
    }
    
    return $routes;
};