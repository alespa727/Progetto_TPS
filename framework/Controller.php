<?php
namespace Core;

use Core\Exceptions\BadRequest;
use Core\Exceptions\InternalServerError;

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
    public function __invoke(Request $request, Params $params): Response
    {
        if ($this->validateRequest($request, $params)) {
            return $this->manageRequest($request, $params);
        } else {
            return $this->manageUnvalidRequest($request, $params);
        }
    }


    /**
     * Parametri del body da controllare con isset
     * @return array ["param1", "param2"]
     */
    public function validateBody(): array
    {
        return [];
    }

    /**
     * Se fai override di questa puoi validare la richiesta prima di gestirla
     * @param Request $request
     * @return bool
     */
    public function validateRequest(Request $request, Params $params): bool
    {
        
        foreach ($this->validateBody() as $key) {
            $value = $request->getBody($key);

            if (!isset($value)) {
                return false;
            }
        }

        return true;
    }
    /**
     * Se fai override di questa puoi gestire una richiesta non valida, di default restitituisce ["code"=>400, "body"=>[], "contentType"=>ContentTypes::Json]
     * @param Request $request
     * @param Params $pathVariables
     * @return Response
     */
    public function manageUnvalidRequest(Request $request, Params $pathVariables): Response
    {

        $body_not_found = [];
    
        foreach ($this->validateBody() as $key) {
            $value = $request->getBody($key);

            if (!isset($value)) {
                $body_not_found[]=$key;
            }
        }


        if(!empty($body_not_found)){
            return Response::new()
                    ->badRequest()
                    ->body([
                        "error"=>"missing body params",
                        "params"=>$body_not_found
                    ]);
        }
        
        throw new BadRequest("");
    }

    /**
     * Gestisce la richiesta
     * @param Request $request
     * @param Params $params
     * @return void
     */
    abstract function manageRequest(Request $request, Params $params): Response;

    protected function view(string $path): \Core\Response
    {

        if (!is_file($path)) {
            throw new InternalServerError("View not found: " . $path);
        }

        return \Core\Response::new()
            ->ok()
            ->body(file_get_contents($path));
    }
}