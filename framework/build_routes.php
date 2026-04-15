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
            ->body(["description" => "route duplicate"]);
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

    $indexedRoutes = [];

    foreach ($routes as $route) {
        $node = &$indexedRoutes;

        $pattern = $route->getPattern();
        $lastIndex = count($pattern) - 1;

        if (empty($pattern)) {
            $indexedRoutes['_' . $route->getMethod()] = $route->toArray();
        }

        foreach ($pattern as $i => $segment) {
            if ($segment[0] === "{" && $segment[strlen($segment) - 1] === '}') {
                $param = explode(":", $segment);
                $paramName = $param[0];
                $node['_param'] = $paramName;

                if (isset($param[1])) {
                    $type = substr($param[1], 1, -1);
                } else {
                    $type = "int";
                }

                if ($type === null || empty($type)) {
                    $node['_type'] = "string";
                } else {
                    $node['_type'] = $type;
                }

                if (!isset($node[$paramName])) {
                    $node[$paramName] = [];
                }

                if (!isset($node[$paramName]['methods'])) {
                    $node[$paramName]['methods'] = [];
                }


                if ($i === $lastIndex) {
                    $node[$paramName]['_' . $route->getMethod()] = $route->toArray();
                    $node[$paramName]['methods'][] = $route->getMethod();
                }

                $node = &$node[$paramName];
                continue;

            }


            if (!isset($node[$segment])) {
                $node[$segment] = [];
            }

            if (!isset($node[$segment]['methods'])) {
                $node[$segment]['methods'] = [];
            }

            if ($i === $lastIndex) {
                $node[$segment]['_' . $route->getMethod()] = $route->toArray();
                $node[$segment]['methods'][] = $route->getMethod();
            }

            $node = &$node[$segment];
        }
    }

    if (!is_dir(__DIR__ . '/cache')) {
        mkdir(__DIR__ . '/cache', 0775, true);
    }

    $p = __DIR__ . '/cache/routes.php';


    $data = "<?php\nreturn " . VarExporter::export($indexedRoutes) . ";\n";
    file_put_contents($p, $data);


    $path = __DIR__ . '/cache/routes.php';
    return (require $path);
};