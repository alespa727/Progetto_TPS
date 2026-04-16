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

#[Route(Method::Delete, ["api", "categories", "{categoryName}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]

#[OA\Delete(
    path: "/api/categories/{categoryName}",
    summary: "Elimina una categoria",
    tags: ["Categories"],
    parameters: [
        new OA\Parameter(
            name: "categoryName",
            description: "url_name della categoria",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string", example: "cpu")
        )
    ],
    responses: [
        new OA\Response(
            response: 204,
            description: "Categoria eliminata"
        ),
        new OA\Response(
            response: 400,
            description: "Categoria non esistente"
        ),
        new OA\Response(
            response: 403,
            description: "Non autorizzato"
        )
    ]
)]
class DeleteCategories extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $name = $params->getString("categoryName");
       
        if(!isset($name)){
            throw new BadRequest("Inserisci un nome valido");
        }

        $db = \DatabaseUtil\Database::getDatabase();
        
         $pr = $db->prepare("SELECT * FROM categories WHERE url_name=?");
       
        $pr->execute([$name]);
        $exists = $pr->fetch(PDO::FETCH_ASSOC);

        if(!$exists){
            throw new BadRequest("Nome non valido");
        }


        $pr = $db->prepare("DELETE FROM categories WHERE name=?");
       
        $success = $pr->execute([$name]);
        if($success){
            $res = Response::new()
                ->noContent();
        }else{
            throw new BadRequest("Nome non valido");
        }
        return $res;

    }   
}
