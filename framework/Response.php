<?php 
namespace Core;
class Response
{
    public $body="";
    public array $headers=[];
    public array $file=[];
    public string $url="";
    
    public int $responseCode=200;

    public function addFile($path, $filename): Response{
        $this->file["path"]=$path;
        $this->file["filename"]=$filename;
        return $this;
    }

    public function addFileInline($path): Response{
        $this->file["path"]=$path;
        return $this;
    }

    

    /**
     * @param array{"code": int, "body": mixed, "contentType": string}
     * @return Response
     */
    public static function create(array $response) {
        $res = new Response();
        $res->responseCode = $response["code"];
        $res->body = $response["body"];
        return $res;
    }

    public static function new(){
        return new Response();
    }

    public function isValid() : bool {
        if(!$this->responseCode){
            return false;
        }

        return true;
    }

    public function ok(): Response{
        $this->responseCode=HttpResponseCodes::OK;
        return $this;
    }

    public function created(): Response{
        $this->responseCode=HttpResponseCodes::CREATED;
        return $this;
    }

    public function redirect(string $url): Response{
        $this->url=$url;
        return $this;
    }

    public function noContent(): Response{
        $this->responseCode=HttpResponseCodes::NO_CONTENT;
        return $this;
    }

    public function badRequest(): Response{
        $this->responseCode=HttpResponseCodes::BAD_REQUEST;
        return $this;
    }

    public function notFound(): Response{
        $this->responseCode=HttpResponseCodes::NOT_FOUND;
        return $this;
    }

    public function methodNotAllowed(): Response{
        $this->responseCode=HttpResponseCodes::METHOD_NOT_ALLOWED;
        return $this;
    }

    public function forbidden(): Response{
        $this->responseCode=HttpResponseCodes::FORBIDDEN;
        return $this;
    }

    public function unauthorized(): Response{
       $this->responseCode=HttpResponseCodes::UNAUTHORIZED;
        return $this;
    }

    public function internalServerError() : Response {
        $this->responseCode=HttpResponseCodes::INTERNAL_SERVER_ERROR;
        return $this;
    }

    public function body(mixed $body): Response{
       $this->body = $body;
        return $this;
    }


    public function status(int $code): Response{
        $this->responseCode = $code;
        return $this;
    }

    public function header(string $header){
        $this->headers[] = $header;
        return $this;
    }

}
