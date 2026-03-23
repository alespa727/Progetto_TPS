<?php

class AuthMiddleware extends Middleware
{

    function manageRequest(Request $request): bool
    {
        $psw = $request->getHeader("Authorization");
        if ($psw !== "123456") {
            http_response_code(401);
            
            echo json_encode(["message" => "Unauthorized"]);
            return false;
        }
        return true;
    }
}