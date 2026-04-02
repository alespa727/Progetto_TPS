<?php
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    private string $method;
    private array $middlewares = [];
    private string $contentType = ContentTypes::Json;
    private string $controllerName;
    private Controller|null $controller;
    private array $pattern;

    public function __construct(string $method, array $pattern, string $className = "", $contentType = ContentTypes::Json)
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

