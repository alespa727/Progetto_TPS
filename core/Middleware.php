<?php

abstract class Middleware {
    public function __invoke(Request $request, callable $next){
        if($this->manageRequest($request))
            $next();
    }

    public abstract function manageRequest(Request $request): bool;
}

