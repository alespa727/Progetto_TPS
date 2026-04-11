<?php
namespace Core;

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
                    ->json(["error" => "Richiesta da hostname non valido"])
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
    }

    static function init(): void
    {
        include_once "functions.php";
        if (routesHaveChanged(Router::$routesPath)) {
            (require "build_routes.php")(Router::$routesPath);

        }
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
                                ->json(['error' => 'Metodo non valido'])
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


                if (array_key_exists($segment, $array) && array_key_exists("_" . $request_method, $array[$segment])) {
                    $route = $array[$segment]["_" . $request_method];
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
            $file = __DIR__ . "/../routes/$className.php";

            if (file_exists($file) && !class_exists($className, false)) {
                require $file;
            }

        }

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
            Router::sendResponse(Response::new()
                ->notFound()
                ->json(['error' => 'Route non trovata']));
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
                    if (!$res || $res->body === null || $res->contentType === null || $res->responseCode === null) {
                        throw new \Core\Exceptions\InternalServerError("Ricontrolla il codice mona");
                    }
                } catch (\Throwable $th) {
                    if (Router::$debug) {
                        $res = Response::new()
                            ->status($th->getCode() ?? 500)
                            ->json([
                                "message" => "Ricontrolla il codice mona",
                                "error" => $th->getMessage()
                            ]);
                    } else {
                        $res = Response::new()
                            ->status($th->getCode() ?? 500)
                            ->json(["error" => $th->getMessage()]);
                    }

                } finally {

                    $end = microtime(true);
                    $elapsed = $end - $start;
                    $res->header("X-time: " . (floor($elapsed * 1000 * 100) / 100) . " ms");
                    Router::sendResponse($res);

                }
            }
        );

        if (!empty($wrong_method_matches)) {
            Router::sendResponse(
                Response::new()
                    ->methodNotAllowed()
                    ->json(['error' => 'Metodo non valido'])
            );
        }
    }

    static function sendHeaders(Response $response)
    {
        http_response_code($response->responseCode);
        foreach ($response->headers as $header) {
            header($header);
        }
        header($response->contentType);
    }

    static function sendResponse(Response $response)
    {
        Router::sendHeaders($response);

        if ($response->contentType === ContentTypes::Json) {
            echo json_encode($response->body);
        } else if($response->contentType === ContentTypes::DownloadFile){
            FileHandler::sendFileDownloadResponse($response->file["path"], $response->file["filename"]);
        }else if($response->contentType === ContentTypes::InlineFile){
            FileHandler::returnInlineFile($response->file["path"]);
        }
        else
        {
            echo $response->body;
        }
        die;
    }
}


