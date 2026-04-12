<?php

use Core\Middleware;
use Core\Request;
use Core\Response;

class AuthMiddleware extends Middleware
{
    function manageRequest(Request $request): bool
    {
        $psw = $request->getHeader("Authorization");
        if ($psw !== "123456") {
            return false;
        }
        return true;
    }

    function getErrorResponse(): Response
    {
        return Response::new()
        ->badRequest()
        ->body(["message" => "Unauthorized"]);
    }
}
