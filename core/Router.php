<?php
class Router
{
    // Gestisce la richiesta ricercando l'uri all'interno delle routes
    /*static function match(Request $request, array $middleware, array $routes)
    {
        $segments = $request->getSegments();
        $method = $request->getMethod();

        // Cicla su ogni route possibile
        foreach ($routes as $route) {
            $requested_middleware_indexes = $route['middleware'];

            // Tutti i middleware richiesti dalla route del ciclo attuale
            $requested_middleware = [];

            foreach ($requested_middleware_indexes as $key => $value) {
                $requested_middleware[] = $middleware[$value];
            }

            // Ex. /users/{userId}
            $pattern = $route['pattern'];

            // Ex. function ($req, $params)
            $handler = $route['handler'];

            // Ex. GET, POST, DELETE, PATCH, PUT
            $route_method = $route['method'];

            // Ex. application/json, text/html
            $contentType = $route['content-type'];

            // Filtro per velocizzare i loop tramite metodo
            if ($route_method !== $method) {
                continue;
            }

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
                // Runna il middleware
                runMiddleware(
                    $request,
                    $requested_middleware,
                    function () use ($handler, $request, $contentType, $params) {
                        // Tipo di risposta
                        header($contentType);

                        // Restituisce in json
                        if ($contentType === ContentTypes::Json)
                            echo json_encode($handler($request, $params));
                        else
                            echo $handler($request, $params);
                    }
                );
                return;
            }
        }

        // Altrimenti 404
        header(ContentTypes::Json);
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }*/

    // Gestisce la richiesta ricercando l'uri all'interno delle routes
    /**
     * @param Request $request
     * @param array<string> $name
     * @param array<Route> $routes
     */
    static function handle(Request $request, array $routes, array $allowedHosts)
    {

        if (!cors($allowedHosts)) {
            $res = new Response();
            $res->created();
            $res->json(["error" => "Richiesta da hostname non valido"]);
            Router::sendResponse($res);
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

                // Runna il middleware
                runMiddleware(
                    $request,
                    $requested_middleware,
                    function () use ($route, $request, $params) {
                        // Tipo di risposta
    
                        try {
                            $res = $route->manageRequest($request, $params);
                            if ($res === null || $res->body === null || $res->contentType === null || $res->responseCode === null) {
                                throw new Exception("Il programmatore ha sbagliato a scrivere il suo codice.. Skill issue");
                            }
                        } catch (\Throwable $th) {
                            $res = Response::create(
                                [
                                    "code" => HttpResponseCode::INTERNAL_SERVER_ERROR,
                                    "body" => $th->getMessage(),
                                    "contentType" => ContentTypes::Text
                                ]
                            );
                        } finally {
                            Router::sendResponse($res);
                        }

                    }
                );
            }
        }

        if (!empty($wrong_method_matches)) {
            $res = new Response();
            $res->methodNotAllowed();
            $res->body(
                ['error' => 'Metodo non valido']
            );
            $res->contentType(ContentTypes::Json);
            Router::sendResponse($res);
        }

        // Altrimenti 404
        $res = new Response();
        $res->notFound();
        $res->json(
            ['error' => 'Route non trovata']
        );
        Router::sendResponse($res);
    }

    static function sendResponse(Response $response)
    {
        if(!$response || !$response->isValid()){
            echo "Il programmatore ha sbagliato a scrivere la risposta alla tua richiesta";
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
    private Controller $controller;
    private array $pattern;

    private function __construct(string $method, array $pattern, string $className)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->controller = new $className();
    }

    static function get(array $pattern, string $handlerClass = ""): Route
    {
        return new Route(Method::Get, $pattern, $handlerClass);
    }

    static function post(array $pattern, string $handlerClass = ""): Route
    {
        return new Route(Method::Post, $pattern, $handlerClass);
    }

    static function patch(array $pattern, string $handlerClass = ""): Route
    {
        return new Route(Method::Patch, $pattern, $handlerClass);
    }


    static function put(array $pattern, string $handlerClass = ""): Route
    {
        return new Route(Method::Put, $pattern, $handlerClass);
    }


    static function delete(array $pattern, string $handlerClass = ""): Route
    {
        return new Route(Method::Delete, $pattern, $handlerClass);
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

    public function prefixPattern(array $pattern): void
    {
        $this->pattern = array_merge($this->pattern, $pattern);
    }
}

