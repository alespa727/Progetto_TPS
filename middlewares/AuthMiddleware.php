<?php

use Core\Middleware;
use Core\Request;
use Core\Response;
use Authorization\Authorization;

class AuthMiddleware extends Middleware
{
    function manageRequest(Request $request): bool
    {
        return Authorization::is_logged_in();
    }

    function getErrorResponse(Request $request): Response
    {
        return Response::new()
        ->unauthorized()
        ->body(["description" => "Not authenticated"]);
    }
}
