<?php

abstract class Controller
{
    public function __invoke(Request $request, array $pathVariables): Response{
        if($this->validateRequest($request, $pathVariables)){
            return $this->manageRequest($request, $pathVariables);
        }else{
            return $this->manageUnvalidRequest($request, $pathVariables);
        }
    }

    /**
     * Se fai override di questa puoi validare la richiesta prima di gestirla
     * @param Request $request
     * @param array $pathVariables
     * @return bool
     */
    public function validateRequest(Request $request, array $pathVariables): bool{
        return true;
    }

    /**
     * Se fai override di questa puoi gestire una richiesta non valida, di default restitituisce ["code"=>400, "body"=>[], "contentType"=>ContentTypes::Json]
     * @param Request $request
     * @param array $pathVariables
     * @return Response
     */
    public function manageUnvalidRequest(Request $request, array $pathVariables): Response{
        return Response::create(["code"=>400, "body"=>[], "contentType"=>ContentTypes::Json]);
    }

    /**
     * Gestisce la richiesta
     * @param Request $request
     * @param array $pathVariables
     * @return void
     */
    abstract function manageRequest(Request $request, array $pathVariables): Response;
}