<?php

use Authorization\Authorization;
use Core\Exceptions\BadRequest;
use Core\Exceptions\NotFound;
use Core\FileHandler;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use OpenApi\Attributes as OA;

#[Route(Method::Get, ["api", "profile", "avatar"], [AuthMiddleware::class], ContentTypes::InlineFile)]

#[OA\Get(
    path: "/api/profile/avatar",
    summary: "Ottieni avatar profilo",
    description: "Ottieni l'immagine associata al tuo profile",
    tags: ["Profile-Images"],
    responses: [
        new OA\Response(
            response: 200,
            description: "OK",
        ),
        new OA\Response(
            response: 404,
            description: "Non trovata",
        )
    ]
)]
class GetProfileImage extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {

        $user = Authorization::getUser();
       
        if(!isset($user["pfp_hash"])) {
            throw new NotFound("Immagine non esistente");
        }
        $path = FileHandler::getFilePath($request, $user["pfp_hash"]);

        if(!$path){
            throw new NotFound();
        }

        return Response::new()
        ->ok()
        ->addFileInline($path);
    }
}