<?php

use Core\Exceptions\NotFound;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;

#[Route(Method::Get, ["api", "users", "{userId}:{int}"], ContentTypes::Json)]
class GetUserById extends Controller
{  


    function manageRequest(Request $request, Params $params): Response
    {

        $db = require_once __DIR__ . '/../database/Database.php';

        $res = new Response();

        $pr = $db->prepare("SELECT id, username, password FROM users WHERE id=:id");
        $pr->execute(["id" => $params->getInt("userId")]);

        $user = $pr->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $res->ok();  
            $res->body($user);
        } else 
            throw new NotFound("utente non trovato");



        return $res;
    }

}
