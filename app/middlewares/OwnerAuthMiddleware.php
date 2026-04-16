<?php

use Core\Middleware;
use Core\Request;
use Core\Response;
use Authorization\Authorization;

class OwnerAuthMiddleware extends Middleware
{
    function manageRequest(Request $request): bool
    {
        return Authorization::is_owner();
    }

    function getErrorResponse(Request $request): Response
    {
        if (!Authorization::is_logged_in()) {
            $res = Response::new()
                ->unauthorized()
                ->body(["description" => "Esegui il login"]);

        } else if (!Authorization::is_owner()) {
            $res = Response::new()
                ->forbidden()
                ->body(["description" => "Non sei autorizzato"]);
                
        }
        return $res;
    }
}
