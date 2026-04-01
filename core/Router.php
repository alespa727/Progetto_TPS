<?php
class Router
{
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

                } else if($isLast){
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
                    print_r($param);
                    $array = &$array[$paramName];

                    $name = substr($paramName, 1, -1);
                    $params[$name] = $segment;

                }



            } else {

                if (array_key_exists($segment, $array) && array_key_exists("_" . $request_method, $array[$segment])) {
                    $route = $array[$segment]["_" . $request_method];
                } else {

                    $param = explode(":", $array["_param"]);
                    $paramName = $param[0];
                    $type = substr($param[1], 1, -1);
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
                            $isValidType = is_numeric($segment);
                            break;
                    }

                    if ($isValidType) {
                        $params[$name] = $segment;
                    }

                    if (array_key_exists("_" . $request_method, $array[$paramName]) && $isValidType) {
                        $route = $array[$paramName]["_" . $request_method];
                    }

                    if (!$route) {
                        if (!$isValidType) {
                            continue;
                        }
                        $methods = $array["methods"];

                        if (!empty($methods)) {
                            Router::sendResponse(
                                Response::new()
                                    ->methodNotAllowed()
                                    ->json(['error' => 'Metodo non valido'])
                            );
                        }
                    }

                }


            }
            $i++;
        }

        $request->setParams($params);
        $className = $route["controller"];
        $file = __DIR__ . "/../routes/$className.php";

        if (file_exists($file)) {
            require $file;
        }

        return $route;
    }


    // Gestisce la richiesta ricercando l'uri all'interno delle routes
    /**
     * @param Request $request
     * @param array<string> $name
     * @param array<array> $routes
     */
    static function handleDirect(Request $request, array $routes, array $allowedHosts, $start)
    {
        Router::handleCors($allowedHosts);

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

        // Importa solo i file necessari
        foreach ($requested_middleware as $key => $middleware) {
            $file = __DIR__ . "/../middlewares/$middleware.php";

            if (file_exists($file)) {
                require_once $file;
            }

        }


        runMiddleware(
            $request,
            $requested_middleware,
            function () use ($routeInstance, $request, $start) {
                try {
                    $res = $routeInstance->manageRequest($request, $request->getParams());
                    if ($res === null || $res->body === null || $res->contentType === null || $res->responseCode === null) {
                        throw new Exception("Ricontrolla il codice mona");
                    }
                } catch (\Exception $th) {
                    $res = Response::new()
                        ->status($th->getCode())
                        ->json([
                            "error" => $th->getMessage()
                        ]);
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
        if (!$response || !$response->isValid()) {
            echo "Ricontrolla il codice mona - 2";
        }

        if ($response->contentType === ContentTypes::Json) {
            echo json_encode($response->body);
        } else {
            echo $response->body;
        }
        die;
    }
}



class Route
{
    private string $method;
    private array $middlewares = [];
    private string $contentType = ContentTypes::Json;
    private string $controllerName;
    private Controller|null $controller;
    private array $pattern;

    private function __construct(string $method, array $pattern, string $className, $contentType = ContentTypes::Json)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->controllerName = $className;
        $this->controller = null;
        $this->contentType = $contentType;
    }

    static function get(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Get, $pattern, $handlerClass, $contentType);
    }

    static function post(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Post, $pattern, $handlerClass, $contentType);
    }

    static function patch(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Patch, $pattern, $handlerClass, $contentType);
    }


    static function put(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Put, $pattern, $handlerClass, $contentType);
    }


    static function delete(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Delete, $pattern, $handlerClass, $contentType);
    }

    static function fromArray(array $route): Route
    {
        $className = $route['controller'];
        $pattern = $route['pattern'] ?? [];
        $method = strtoupper($route['method'] ?? '');
        $contentType = $route['contentType'] ?? ContentTypes::Json;

        switch ($method) {
            case "GET":
                return new Route('GET', $pattern, $className, $contentType);
            case "POST":
                return new Route('POST', $pattern, $className, $contentType);
            case "DELETE":
                return new Route('DELETE', $pattern, $className, $contentType);
            case "PUT":
                return new Route('PUT', $pattern, $className, $contentType);
            case "PATCH":
                return new Route('PATCH', $pattern, $className, $contentType);
            default:
                throw new Exception("Metodo non valido: $method");
        }
    }


    public function middleware(string $middleware): Route
    {
        $this->middlewares[] = $middleware;
        return $this;
    }


    public function contentType(string $contentType): Route
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function manageRequest(Request $request, array $params): Response
    {
        if ($this->controller === null) {
            $className = $this->controllerName;
            $this->controller = new $className();
        }
        return ($this->controller)($request, $params);
    }
    public function getMethod(): string
    {
        return $this->method;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getPattern(): array
    {
        return $this->pattern;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }


    public function getController(): string
    {
        return $this->controllerName;
    }

    public function prefixPattern(array $pattern): void
    {
        $this->pattern = array_merge($this->pattern, $pattern);
    }

    public function toArray(): array
    {
        return [
            "method" => $this->getMethod(),
            "pattern" => $this->getPattern(),
            "contentType" => $this->getContentType(),
            "middlewares" => $this->getMiddlewares(),
            "controller" => $this->getController()
        ];
    }
}

