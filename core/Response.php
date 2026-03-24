<?php 

class Response
{
    public $body="";
    public array $headers=[];
    
    public string $contentType;
    
    public int $responseCode;

    /**
     * @param array{"code": int, "body": mixed, "contentType": string}
     * @return Response
     */
    public static function create(array $response) {
        $res = new Response();
        $res->responseCode = $response["code"];
        $res->body = $response["body"];
        $res->contentType = $response["contentType"];
        return $res;
    }

    public function isValid() : bool {
        if(!$this->responseCode){
            return false;
        }

        if(!$this->contentType){
            return false;
        }

        return true;
    }

    public function ok(): Response{
        $this->responseCode=HttpResponseCode::OK;
        return $this;
    }

    public function created(): Response{
        $this->responseCode=HttpResponseCode::CREATED;
        return $this;
    }

    public function noContent(): Response{
        $this->responseCode=HttpResponseCode::NO_CONTENT;
        return $this;
    }

    public function badRequest(): Response{
        $this->responseCode=HttpResponseCode::BAD_REQUEST;
        return $this;
    }

    public function notFound(): Response{
        $this->responseCode=HttpResponseCode::NOT_FOUND;
        return $this;
    }

    public function methodNotAllowed(): Response{
        $this->responseCode=HttpResponseCode::METHOD_NOT_ALLOWED;
        return $this;
    }

    public function forbidden(): Response{
        $this->responseCode=HttpResponseCode::FORBIDDEN;
        return $this;
    }

    public function unauthorized(): Response{
       $this->responseCode=HttpResponseCode::UNAUTHORIZED;
        return $this;
    }

    public function internalServerError() : Response {
        $this->responseCode=HttpResponseCode::INTERNAL_SERVER_ERROR;
        return $this;
    }

    public function body(mixed $body): Response{
       $this->body = $body;
        return $this;
    }

    public function json(array $body): Response{
        $this->body = $body;
        $this->contentType = ContentTypes::Json;
        return $this;
    }

    public function header(string $header){
        $this->headers[] = $header;
        return $this;
    }

    public function contentType(string $contentType): Response{
       $this->contentType = $contentType;
        return $this;
    }

}
