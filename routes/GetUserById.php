<?php

use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\HttpResponseCodes; 
use Core\ContentTypes;
use Core\Params;

#[Route(Method::Get, ["api", "users", "{userId}:{string}"], ContentTypes::Json)] 
class GetUserById extends Controller
{    
    private $users = [
        "ale" => [  
            "id" => 0, 
            "username" => "ale",
            "description" => "desc1"  
        ], 
        "tommy" => [  
            "id" => 1,  
            "username" => "tommy",  
            "description" => "tommy e' stato qui'"
        ],     
        "ashan" => [
            "id" => 2,
            "username" => "ashan",
            "description" => "bhiwehbfdsiyhdiybsdidhbashbiydasihbdasuyhb"
        ],
    ]; 

    function manageRequest(Request $request, Params $params): Response
    {

        try {
            $userId = $params->getString("userId");
            $user = $this->users[$userId];

            $res = new Response();
            $res->ok();
            $res->json($user);
        } catch (\Throwable $th) {
            throw new Exception("Inserire un id valido", HttpResponseCodes::BAD_REQUEST);
        }


        return $res;
    }

}
