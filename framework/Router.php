<?php
namespace Core;

use Core\Exceptions\MethodNotAllowed;

class Router
{

    private static $routesPath = "";
    private static $middlewarePath = "";
    private static $debug = false;

    static function handleCors(array $allowedHosts)
    {
        if (!cors($allowedHosts)) {
            Router::sendResponse(
                Response::new()
                    ->unauthorized()
                    ->body(["error" => "Richiesta da hostname non valido"]),
                ContentTypes::Json
            );
        }
    }

    /**
     * @param array{routes: string, middlewares: string, debug?: bool} $config
     * @return void
     */
    static function loadConfig(array $config): void
    {
        Router::$routesPath = $config["routes"];
        Router::$middlewarePath = $config["middlewares"];
        Router::$debug = $config["debug"] ?? false;
        if (Router::$debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
    }

    static function init(): void
    {
        include_once "functions.php";
        $cacheExists = !empty(glob(__DIR__ . '/cache/routes_*.php'));
        if ( routesHaveChanged(Router::$routesPath)) {
            (require "build_routes.php")(Router::$routesPath);

        }
    }

    public static function getRoutes(): array
    {
        return (require "get_all_routes.php")(Router::$routesPath);
    }

    static function findMatch(Request &$request, array $routes): array|null
    {
        $route = null;
        $array = $routes;
        $segments = $request->getSegments();
        $request_method = $request->getMethod();
        $params = [];


        $i = 0;

        foreach ($segments as $key => $segment) {
            $isLast = $i === count($segments) - 1;

            if ($i === 0) {
                if ($isLast && array_key_exists("_" . $request_method, $array)) {

                    $route = $array["_" . $request_method];

                } else if ($isLast) {
                    $methods = $array["methods"];

                    if (!empty($methods)) {
                        Router::sendResponse(
                            Response::new()
                                ->methodNotAllowed()
                                ->body(['error' => 'Metodo non valido']),
                            ContentTypes::Json
                        );
                    }
                }
                $i++;
                continue;
            }

            if (!$isLast) {

                if (array_key_exists($segment, $array)) {
                    $array = &$array[$segment];
                } else {

                    $param = explode(":", $array["_param"]);
                    $paramName = $param[0];
                    $type = $param[1];
                    $array = &$array[$paramName];

                    $name = substr($paramName, 1, -1);
                    $params[$name] = $segment;

                }



            } else {
        
                if (array_key_exists($segment, $array)) {
                    if (array_key_exists("_" . $request_method, $array[$segment]))
                        $route = $array[$segment]["_" . $request_method];
                    else if (count($array[$segment]) > 0) {
                        $res = Response::new()
                            ->status(HttpResponseCodes::METHOD_NOT_ALLOWED)
                            ->body(["description"=>"metodo non valido"]);
                        Router::sendResponse($res, ContentTypes::Json);
                    }

                } else if (array_key_exists("_param", $array)) {

                    $param = explode(":", $array["_param"]);

                    $paramName = $param[0];

                    $type = $array["_type"];

                    $name = substr($paramName, 1, -1);

                    $isValidType = false;

                    switch ($type) {
                        case 'int':
                            $isValidType = is_numeric($segment);
                            break;
                        case 'string':
                            $isValidType = !is_numeric($segment);
                            break;
                        default:
                            $isValidType = true;
                            break;
                    }

                    if ($isValidType) {
                        $params[$name] = $segment;
                    }

                    if (array_key_exists("_" . $request_method, $array[$paramName])) {
                        $route = $array[$paramName]["_" . $request_method];
                    }

                }


            }
            $i++;
        }

        if ($route) {
            $request->setParams($params);
            $className = $route["controller"];

            $path = $route["controller_path"];

            if (file_exists($path) && !class_exists($className, false)) {
                require $path;
            }

        }/*else{

       }*/

        return $route;
    }


    // Gestisce la richiesta ricercando l'uri all'interno delle routes
    /**
     * @param array<string> $allowedHosts
     * @param $start
     */
    static function handleDirect(array $allowedHosts, $start)
    {
        $request = new Request();
        Router::handleCors($allowedHosts);

        $routes = [];
        if (!empty($request->getSegments())) {
            $routes = require "cache/routes_" . $request->getSegments()[0] . ".php";
        }

        $route = Router::findMatch($request, $routes);
        if (!$route) {
            // Altrimenti 404
            Router::sendResponse(
                Response::new()
                    ->notFound()
                    ->body(['error' => 'Route non trovata']),
                ContentTypes::Json
            );
        }

        $requiredFiles = [];
        $routeInstance = Route::fromArray($route);
        $requested_middleware = $routeInstance->getMiddlewares();

        importMiddlewares($requested_middleware);

        runMiddleware(
            $request,
            $requested_middleware,
            function () use ($routeInstance, $request, $start) {
                $res = null;

                try {
                    $res = $routeInstance->manageRequest($request, new Params($request->getParams()));
                    if (!$res || $res->body === null || $routeInstance->getContentType() === null || $res->responseCode === null) {
                        throw new \Core\Exceptions\InternalServerError("Ricontrolla il codice mona");
                    }
                } catch (\Throwable $th) {
                    if (Router::$debug) {
                        $res = Response::new()
                            ->status($th->getCode() ?? 500)
                            ->body([
                                "message" => "Ricontrolla il codice mona",
                                "error" => $th->getMessage()
                            ]);

                        Router::sendResponse($res, ContentTypes::Json);
                    } else {
                        $res = Response::new()
                            ->status($th->getCode() ?? 500)
                            ->body([
                                "error" => $th->getMessage() ?? "Internal Server Error"
                            ]);

                        Router::sendResponse($res, ContentTypes::Json);

                    }

                } finally {

                    $end = microtime(true);
                    $elapsed = $end - $start;
                    $res->header("X-time: " . (floor($elapsed * 1000 * 100) / 100) . " ms");

                    Router::sendResponse($res, $routeInstance->getContentType());

                }
            }
        );

        if (!empty($wrong_method_matches)) {
            Router::sendResponse(
                Response::new()
                    ->methodNotAllowed()
                    ->body(['error' => 'Metodo non valido']),
                $routeInstance->getContentType()
            );
        }
    }

    static function sendHeaders(Response $response, string $type)
    {
        http_response_code($response->responseCode);
        foreach ($response->headers as $header) {
            header($header);
        }
        header($type);
    }

    static function sendResponse(Response $response, string $contentType)
    {
        Router::sendHeaders($response, $contentType);

        if ($contentType === ContentTypes::Json) {
            echo json_encode($response->body);
        } else if ($contentType === ContentTypes::DownloadFile) {
            FileHandler::sendFileDownloadResponse($response->file["path"], $response->file["filename"]);
        } else if ($contentType === ContentTypes::InlineFile) {
            FileHandler::returnInlineFile($response->file["path"]);
        } else {
            echo $response->body;
        }
        die;
    }
}


