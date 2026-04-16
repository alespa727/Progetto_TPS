<?php

use Core\Exceptions\BadRequest;
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

#[Route(Method::Get, ["api", "categories"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Get(
    path: "/api/categories",
    summary: "Lista di tutte le categorie",
    tags: ["Categories"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Lista categorie",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "ASUS"),
                        new OA\Property(property: "url_name", type: "string", example: "asus")
                    ]
                )
            )
        ),
        new OA\Response(
            response: 400,
            description: "Errore richiesta"
        )
    ]
)]
class GetAllCategories extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("SELECT * FROM categories");
        
        $success = $pr->execute();
        $res = $pr->fetchAll(PDO::FETCH_ASSOC);
        if($success){
            $res = Response::new()
                ->ok()
                ->body($res);
        }else{
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    } 
}
