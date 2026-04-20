<?php

namespace Core;

use Attribute;

/**
 * Attributo PHP che definisce una route HTTP associata a una classe controller.
 *
 * Può essere applicato più volte sulla stessa classe per registrare route multiple.
 * Usato da {@see RouteBuilder} per la discovery automatica dei controller.
 *
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    private string $method;
    private array $middlewares = [];
    private string $contentType = ContentTypes::Json;
    private string $controllerName;
    private string $controllerPath;
    private Controller|null $controller;

    private array $pattern;

      /**
     * @param string $method        Metodo HTTP (es. 'GET', 'POST').
     * @param array  $pattern       Segmenti URI (es. `['users', '{id:int}']`).
     * @param array  $middlewares   Lista di classi middleware da eseguire prima del controller.
     * @param string $contentType   Content-Type della risposta (default {@see ContentTypes::Json}).
     * @param string $className     FQCN del controller associato.
     * @param string $controllerPath Percorso assoluto del file del controller.
     */
    public function __construct(string $method, array $pattern, array $middlewares, string $contentType = ContentTypes::Json, $className = "", $controllerPath = "")
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->controllerName = $className;
        $this->controller = null;
        $this->middlewares = $middlewares;
        $this->contentType = $contentType;
        $this->controllerPath = $controllerPath;

    }

     /**
     * Istanzia il controller (se necessario) e invoca il suo handler `__invoke`.
     *
     * @param Request $request Oggetto della richiesta HTTP corrente.
     * @param Params  $params  Parametri estratti dall'URI.
     * @return Response        Risposta prodotta dal controller.
     */
    public function manageRequest(Request $request, Params $params): Response
    {
        if ($this->controller === null) {
            $className = $this->controllerName;
            $this->controller = new $className();
        }
        return ($this->controller)($request, $params);
    }

    /**
     * Serializza la route in un array associativo, normalizzando i segmenti dinamici
     * (es. `{id:int}` → `{id}`).
     *
     * @return array Array con chiavi: `uri`, `method`, `pattern`, `contentType`,
     *               `middlewares`, `controller`, `controller_path`.
     */
    public function toArray(): array
    {
        $uri = '/' . implode('/', array_map(
            fn($p) => preg_replace('/:\{[^}]+\}/', '', $p),
            $this->getPattern()
        ));

        return [
            'uri' => $uri,
            'method' => $this->getMethod(),
            'pattern' => $this->getPattern(),
            'contentType' => $this->getContentType(),
            'middlewares' => $this->getMiddlewares(),
            'controller' => $this->getController(),
            'controller_path' => $this->getControllerPath()
        ];
    }

    /**
     * Istanzia una Route a partire da un array associativo (es. letto dalla cache).
     *
     * @param array $route Array con chiavi: `controller`, `controller_path`, `middlewares`,
     *                     `pattern`, `method`, `contentType`.
     * @return Route       Istanza configurata per il metodo HTTP specificato.
     *
     * @throws \Exception Se il metodo HTTP non è tra GET, POST, PUT, PATCH, DELETE.
     */
    static function fromArray(array $route): Route
    {
        $className = $route['controller'];
        $path = $route['controller_path'];
        $middlewares = $route["middlewares"] ?? [];
        $pattern = $route['pattern'] ?? [];
        $method = strtoupper($route['method'] ?? '');
        $contentType = $route['contentType'] ?? ContentTypes::Json;

        switch ($method) {
            case "GET":
                return new Route('GET', $pattern, $middlewares, $contentType, $className, $path);
            case "POST":
                return new Route('POST', $pattern, $middlewares, $contentType, $className, $path);
            case "DELETE":
                return new Route('DELETE', $pattern, $middlewares, $contentType, $className, $path);
            case "PUT":
                return new Route('PUT', $pattern, $middlewares, $contentType, $className, $path);
            case "PATCH":
                return new Route('PATCH', $pattern, $middlewares, $contentType, $className, $path);
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

    
}
