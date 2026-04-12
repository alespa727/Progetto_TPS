<?php

use Core\Middleware;
use Core\Request;
use Core\Response;

class SalutaTommy extends Middleware
{
    function manageRequest(Request $request): bool
    {
        return false;
    }

    function getErrorResponse(): Response
    {
        return Response::new()
        ->forbidden()
        ->body(["Vai in mona tommy"]);
    }
}
