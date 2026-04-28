<?php

use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use OpenApi\Attributes as OA;

#[Route(Method::Get, ["mkdocs"], [], ContentTypes::Redirect)]
#[OA\Get( 
    path: "/mkdocs",
    summary: "Welcomes the user into my web service",
    responses: [
        new OA\Response(response: 200, description: "OK")
    ]
)]
class Mkdocs extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {


        return Response::new()
            ->ok()
            ->redirect(" http://".$_SERVER["HTTP_HOST"].":8080");
            
    }
}
