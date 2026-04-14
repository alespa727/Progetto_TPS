<?php

namespace Core;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    private string $method;
    private array $middlewares = [];
    private string $contentType = ContentTypes::Json;
    private string $controllerName;
    private string $controllerPath;
    private Controller|null $controller;

    private ApiDoc|null $docs;
    private array $pattern;

    public function __construct(string $method, array $pattern, array $middlewares, string $contentType = ContentTypes::Json, $className = "", $controllerPath = "", $docs = null)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->controllerName = $className;
        $this->controller = null;
        $this->middlewares = $middlewares;
        $this->contentType = $contentType;
        $this->controllerPath = $controllerPath;
        $this->docs = $docs;
    }
    static function get(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Get, $pattern, [], $handlerClass, $contentType);
    }

    static function post(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Post, $pattern, [], $handlerClass, $contentType);
    }

    static function patch(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Patch, $pattern, [], $handlerClass, $contentType);
    }

    static function put(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Put, $pattern, [], $handlerClass, $contentType);
    }

    static function delete(array $pattern, string $handlerClass = "", string $contentType = ContentTypes::Json): Route
    {
        return new Route(Method::Delete, $pattern, [], $handlerClass, $contentType);
    }

    static function fromArray(array $route): Route
    {
        $className = $route['controller'];
        $path = $route['controller_path'];
        $docs = $route['docs'] ?? null;
        $middlewares = $route["middlewares"] ?? [];
        $pattern = $route['pattern'] ?? [];
        $method = strtoupper($route['method'] ?? '');
        $contentType = $route['contentType'] ?? ContentTypes::Json;

        switch ($method) {
            case "GET":
                return new Route('GET', $pattern, $middlewares, $contentType, $className, $path, $docs);
            case "POST":
                return new Route('POST', $pattern, $middlewares, $contentType, $className, $path, $docs);
            case "DELETE":
                return new Route('DELETE', $pattern, $middlewares, $contentType, $className, $path, $docs);
            case "PUT":
                return new Route('PUT', $pattern, $middlewares, $contentType, $className, $path, $docs);
            case "PATCH":
                return new Route('PATCH', $pattern, $middlewares, $contentType, $className, $path, $docs);
            default:
                throw new \Exception("Metodo non valido: $method");
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

    public function manageRequest(Request $request, Params $params): Response
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

    public function getControllerPath(): string
    {
        return $this->controllerPath;
    }

    public function prefixPattern(array $pattern): void
    {
        $this->pattern = array_merge($this->pattern, $pattern);
    }

    public function toArray(): array
    {
        $uri = '/' . implode('/', array_map(
            fn($p) => preg_replace('/\{(\w+)\}:[^}]+/', '{$1}', $p),
            $this->getPattern()
        ));

        return [
            'uri' => $uri,
            'method' => $this->getMethod(),
            'pattern' => $this->getPattern(),
            'contentType' => $this->getContentType(),
            'middlewares' => $this->getMiddlewares(),
            'controller' => $this->getController(),
            'controller_path' => $this->getControllerPath(),
            'docs' => $this->docs,
        ];
    }
}
