<?php
namespace Core;

/**
 * Classe base astratta per tutti i controller dell'applicazione.
 *
 * Il flusso di esecuzione è: validazione tramite {@see validateRequest()} →
 * se valida {@see manageRequest()}, altrimenti {@see manageUnvalidRequest()}.
 *
 * I controller concreti devono implementare {@see manageRequest()}
 * 
 */
abstract class Controller
{
    public function __invoke(Request $request, Params $params): Response{
        if($this->validateRequest($request, $params)){
            return $this->manageRequest($request, $params);
        }else{
            return $this->manageUnvalidRequest($request, $params);
        }
    }

    /**
     * Se fai override di questa puoi validare la richiesta prima di gestirla
     * @param Request $request
     * @return bool
     */
    public function validateRequest(Request $request, Params $params): bool{
        return true;
    }

    /**
     * Se fai override di questa puoi gestire una richiesta non valida, di default restitituisce ["code"=>400, "body"=>[], "contentType"=>ContentTypes::Json]
     * @param Request $request
     * @param array $pathVariables
     * @return Response
     */
    public function manageUnvalidRequest(Request $request, Params $pathVariables): Response{
        return Response::create(["code"=>400, "body"=>[], "contentType"=>ContentTypes::Json]);
    }

    /**
     * Gestisce la richiesta
     * @param Request $request
     * @param Params $params
     * @return void
     */
    abstract function manageRequest(Request $request, Params $params): Response;
}