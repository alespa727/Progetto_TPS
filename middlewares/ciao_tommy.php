<?php

class SalutaTommy extends Middleware
{

    function manageRequest(Request $request): bool
    {
        return false;
    }

    function getErrorResponse(): Response{
        return Response::new()
        ->forbidden()
        ->json(["Vai in mona tommy"]);
    }
    
}