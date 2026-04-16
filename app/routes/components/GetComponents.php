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

#[Route(Method::Get, ["api", "components", "{url_name}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Get(
    path: "/api/components/{url_name}",
    summary: "Dettaglio componente",
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
            response: 200,
            description: "OK",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "url_name", type: "string"),
                    new OA\Property(property: "price", type: "integer", nullable: true),
                    new OA\Property(property: "description", type: "string", nullable: true)
                ],
                type: "object"
            )
        ),
        new OA\Response(
            response: 404,
            description: "Componente non esistente"
        )
    ]
)]
class GetComponents extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $url_name=$params->getString("url_name");
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("SELECT * FROM components WHERE url_name=?");
       
        $success = $pr->execute([$url_name]);
        $res = $pr->fetch(PDO::FETCH_ASSOC);
        if($success && $res){
            $res = Response::new()
                ->created()
                ->body($res);
        }else{
            throw new NotFound("Componente non esistente");
        }
        return $res;

    } 
}
