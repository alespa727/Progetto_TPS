<?php

use Core\Exceptions\BadRequest;
use Core\Exceptions\NotFound;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Core\ApiDoc;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OpenApi\Attributes as OA;

#[Route(Method::Delete, ["api", "components", "{url_name}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Delete(
    path: "/api/components/{url_name}",
    summary: "Cancella componente componente",
    tags: ["Components"],
    parameters: [
        new OA\Parameter(
            name: "url_name",
            description: "url-name del componente",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string")
        )
    ],
    responses: [
        new OA\Response(
            response: 204,
            description: "OK"
        ),
        new OA\Response(
            response: 404,
            description: "Componente non esistente"
        )
    ]
)]
class DeleteComponents extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $url_name=$params->getString("url_name");
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("SELECT * FROM components WHERE url_name=?");
       
        $success = $pr->execute([$url_name]);
        $res = $pr->fetch(PDO::FETCH_ASSOC);

        $pr = $db->prepare("DELETE FROM components WHERE url_name=?");
       
        $success = $pr->execute([$url_name]);
    
        if($success && $res){
            $res = Response::new()
                ->noContent();
        }else{
            throw new NotFound("Componente non esistente");
        }
        return $res;

    } 
}
