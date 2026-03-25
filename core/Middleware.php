<?php

abstract class Middleware {
    public function __invoke(Request $request, callable $next){
        if($this->manageRequest($request))
            $next();
    }


    /**
     * Da gestire la richiesta, 
     * dopo questo metodo verrà chiamata la successa successiva se restituisci true,
     * altrimenti ferma la richiesta se restituisci
     * @param Request $request
     * @param array $pathVariables
     * @return bool
     */
    public abstract function manageRequest(Request $request): bool;
}

