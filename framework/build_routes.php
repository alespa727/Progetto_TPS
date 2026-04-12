<?php
namespace Core;
use Symfony\Component\VarExporter\VarExporter;


return function (string $path, Request $request = new Request()): array|null {

    include_once "functions.php";

    $before = get_declared_classes();

    foreach (glob($path.'/*.php') as $file) {
        require_once $file;
    }

    $after = get_declared_classes();
    $newClasses = array_diff($after, $before);

    $routes = [];

    foreach ($newClasses as $className) {
        $reflection = new \ReflectionClass($className);
        $attributes = $reflection->getAttributes(Route::class);

        foreach ($attributes as $attr) {
            /**
             * @var Route $route
             */
            $route = $attr->newInstance();
            
            $routeArray = $route->toArray();
            $routeArray["controller"] = $className;
            $routes[] = Route::fromArray($routeArray);

            print_r($routes);
        }
    }

    $indexedRoutes = [];

    foreach ($routes as $route) {
        $node = &$indexedRoutes;

        $pattern = $route->getPattern();
        $lastIndex = count($pattern) - 1;


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
                    $node['_type'] = "int";
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

    foreach ($indexedRoutes as $prefix => $routes) {
        $p = __DIR__ . '/cache/routes_' . $prefix . '.php';


        $data = "<?php\nreturn " . VarExporter::export($routes) . ";\n";
        file_put_contents($p, $data);
    }
    $path = __DIR__ . '/cache/routes_' . $request->getSegments()[0] . '.php';
    return (require $path);
};