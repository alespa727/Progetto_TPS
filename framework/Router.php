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

    static function init(): void
    {
        Router::$routesPath = Config::path("directories.controllers");
        Router::$middlewarePath =Config::path("directories.middlewares");
        Router::$debug = Config::get("app.debug");


        if (Router::$debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } 
        include_once "functions.php";
        if (true)/*routesHaveChanged(Router::$routesPath)) */ {
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

        if (empty($segments)) {
            if (isset($array["_" . $request_method])) {
                $route = $array["_" . $request_method];
            }
        }

        foreach ($segments as $key => $segment) {
            $isLast = $i === count($segments) - 1;


            if (!$isLast) {

                if (array_key_exists($segment, $array)) {
                    $array = &$array[$segment];

                } else {
                    
                    if (array_key_exists("_param", $array)){
                        $param = explode(":", $array["_param"]);
                        continue;
                    }
                        
                    $paramName = null;
                    if(isset($param))
                        $paramName = $param[0];

                    if (array_key_exists("_type", $array)){
                        $type = $array["_type"];
                        continue;
                    }
                        
                    if(!$paramName) continue;
                    $name = substr($paramName, 1, -1);

                    $isValidType = false;

                    $array = &$array[$paramName];

                    $params[$name] = $segment;

                }
            } else {
                if (!is_array($array)) {
                    $res = Response::new()
                        ->status(HttpResponseCodes::NOT_FOUND)
                        ->body(["description" => "route non trovata"]);
                    Router::sendResponse($res, ContentTypes::Json);
                }

                if (array_key_exists($segment, $array)) {
                    if (array_key_exists("_" . $request_method, $array[$segment]))
                        $route = $array[$segment]["_" . $request_method];
                    else if (count($array[$segment]["methods"]) > 0) {
                        $res = Response::new()
                            ->status(HttpResponseCodes::METHOD_NOT_ALLOWED)
                            ->body(["description" => "metodo non valido"]);
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
     * @param $start
     */
    static function handleDirect($start)
    {
        $request = new Request();
        Router::handleCors(Config::get('app.allowed_hosts', []));

        $routes = (require "cache/routes.php");


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


