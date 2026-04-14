<?php 
namespace Core;

class Request
{
    private string $method;
    private string $path;
    private array $segments;
    private array $query;
    private array $body;
    private array $headers;

    private array $params;


    public function __construct()
    {
        // GET
        $this->method = $_SERVER['REQUEST_METHOD'];

        // /api/users
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // /api/users
        // -> ["api", "users"]
        $this->segments = getExplodedUri();
    
        $this->query = $_GET;

        $this->body = json_decode(file_get_contents("php://input"), true) ?? [];

        $this->headers = getallheaders() ?? [];

        if(empty($this->segments)){
            return;
        }
        
        // /api/users?name=ale
        // -> ["api", "users?name=ale"] diventa
        // -> ["api", "users"]
        if(str_contains($this->segments[count($this->segments)-1], "?")){
            $this->segments[count($this->segments)-1] = str_replace("?", "/", $this->segments[count($this->segments)-1]);
            $this->segments[count($this->segments)-1] = explode("/", $this->segments[count($this->segments)-1])[0];
        }
    }

    public function setParams(array $p){
        $this->params=$p;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getQuery(string $key)
    {
        return $key ? ($this->query[$key] ?? null) : $this->query;
    }

    public function getBody(string $key)
    {
        $value = $key ? ($this->body[$key] ?? null) : $this->body;
        
        return $value;
    }

    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
