<?php
class Router
{
    // Gestisce la richiesta ricercando l'uri all'interno delle routes
    /**
     * @param Request $request
     * @param array<string> $name
     * @param array<Route> $routes
     */
    static function handle(Request $request, array $routes, array $allowedHosts)
    {
        $start = microtime(true);
        if (!cors($allowedHosts)) {
            Router::sendResponse(
                Response::new()
                    ->unauthorized()
                    ->json(["error" => "Richiesta da hostname non valido"])
            );
        }

        $segments = $request->getSegments();
        $request_method = $request->getMethod();

        $wrong_method_matches = [];

        // Cicla su ogni route possibile
        foreach ($routes as $route) {
            $requested_middleware = $route->getMiddlewares();

            // Ex. /users/{userId}
            $pattern = $route->getPattern();

            // Ex. GET, POST, DELETE, PATCH, PUT
            $route_method = $route->getMethod();

            // Filtro per velocizzare i loop tramite numero di segmenti dell'uri
            if (count($pattern) !== count($segments)) {
                continue;
            }

            // Lista di parametri (quelli circondati da parentesi grafe)
            // Ex. {userId}
            $params = [];

            // Condizione se il pattern è uguale
            $matched = true;


            foreach ($pattern as $i => $seg) {
                $current = $segments[$i];

                // Se è circondato da parentesi allora salva il segmento all'interno dei parametri
                if ($seg[0] === '{' && $seg[strlen($seg) - 1] === '}') {
                    $paramName = substr($seg, 1, -1);
                    $params[$paramName] = $current;
                } else // Altrimenti controlla se il segmento attuale è uguale a quello della route
                {
                    if ($seg !== $current) {
                        $matched = false;
                        break;
                    }
                }
            }

            // Se è uguale
            if ($matched) {
                if ($route_method !== $request_method) {
                    $wrong_method_matches[] = $route_method;
                    continue;
                }

                $end = microtime(true);

                $elapsed = $end - $start;
                echo "Tempo impiegato: " . ($elapsed * 1000) . " ms\n";

                // Runna il middleware
                runMiddleware(
                    $request,
                    $requested_middleware,
                    function () use ($route, $request, $params) {
                        // Tipo di risposta
    
                        try {
                            $res = $route->manageRequest($request, $params);
                            if ($res === null || $res->body === null || $res->contentType === null || $res->responseCode === null) {
                                throw new Exception("Ricontrolla il codice mona");
                            }
                        } catch (\Throwable $th) {
                            $res = Response::new()
                                ->internalServerError()
                                ->json([
                                    "body" => $th->getMessage()
                                ]);
                        } finally {

                            Router::sendResponse($res);
                        }

                    }
                );
            }
        }

        if (!empty($wrong_method_matches)) {
            Router::sendResponse(
                Response::new()
                    ->methodNotAllowed()
                    ->json(['error' => 'Metodo non valido'])
            );
        }

        // Altrimenti 404
        Router::sendResponse(Response::new()
            ->notFound()
            ->json(['error' => 'Route non trovata']));
    }


    // Gestisce la richiesta ricercando l'uri all'interno delle routes
    /**
     * @param Request $request
     * @param array<string> $name
     * @param array<array> $routes
     */
    static function handle2(Request $request, array $routes, array $allowedHosts)
    {

        $start = microtime(true);
        if (!cors($allowedHosts)) {
            Router::sendResponse(
                Response::new()
                    ->unauthorized()
                    ->json(["error" => "Richiesta da hostname non valido"])
            );
        }

        $route = null;
        $array = &$routes;
        $segments = $request->getSegments();
        $request_method = $request->getMethod();
        $params = [];

        $i = 0;
        foreach ($segments as $key => $segment) {
            $isLast = $i === count($segments) - 1;

            if ($i === 0) {
                if ($isLast && array_key_exists("_" . $request_method, $array)) {
                    $route = $array["_" . $request_method];
                }
                $i++;
                continue;
            }

            if (!$isLast) {

                if (array_key_exists($segment, $array)) {
                    $array = &$array[$segment];
                } else {

                    $paramName = $array["_param"];
                    $array = &$array[$paramName];

                    $name = substr($paramName, 1, -1);
                    $params[$name] = $segment;

                }



            } else {

                if (array_key_exists($segment, $array) && array_key_exists("_" . $request_method, $array[$segment])) {
                    $route = $array[$segment]["_" . $request_method];
                } else {

                    $paramName = $array["_param"];

                    $name = substr($paramName, 1, -1);
                    $params[$name] = $segment;

                    if (array_key_exists("_" . $request_method, $array[$paramName])) {
                        $route = $array[$paramName]["_" . $request_method];
                    }

                    if (!$route) {
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

        if (!$route) {
            // Altrimenti 404
            Router::sendResponse(Response::new()
                ->notFound()
                ->json(['error' => 'Route non trovata']));
        }

        $routeInstance = Route::fromArray($route);
        $requested_middleware = $routeInstance->getMiddlewares();

        $end = microtime(true);

        $elapsed = $end - $start;
        echo "Tempo impiegato: " . ($elapsed * 1000) . " ms\n";

        runMiddleware(
            $request,
            $requested_middleware,
            function () use ($routeInstance, $request, $params) {
                try {
                    $res = $routeInstance->manageRequest($request, $params);
                    if ($res === null || $res->body === null || $res->contentType === null || $res->responseCode === null) {
                        throw new Exception("Ricontrolla il codice mona");
                    }
                } catch (\Throwable $th) {
                    $res = Response::new()
                        ->internalServerError()
                        ->json([
                            "body" => $th->getMessage()
                        ]);
                } finally {
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


    static function sendResponse(Response $response)
    {
        if (!$response || !$response->isValid()) {
            echo "Ricontrolla il codice mona - 2";
        }
        http_response_code($response->responseCode);
        foreach ($response->headers as $header) {
            header($header);
        }
        header($response->contentType);
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

