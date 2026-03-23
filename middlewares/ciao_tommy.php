<?php

class SalutaTommy extends Middleware
{

    function manageRequest(Request $request): bool
    {
        echo "Vai in mona tommy";
        http_response_code(401);
        return false;
    }
    
}