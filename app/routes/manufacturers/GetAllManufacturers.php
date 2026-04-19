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
use Authorization\Authorization;
use OpenApi\Attributes as OA;

#[Route(Method::Get, ["api", "manufacturers"], [], ContentTypes::Json)]
#[OA\Tag(name: "Manufacturers")]
#[OA\PathItem(path: "/api/manufacturers")]
#[OA\Get(
    path: "/api/manufacturers",
    summary: "Lista di tutti i produttori",
    tags: ["Manufacturers"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Lista produttori",
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
class GetAllManufacturers extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
      
        $db = \DatabaseUtil\Database::getDatabase();

        $pr = $db->prepare("SELECT name, url_name FROM manufacturers ORDER BY url_name");
       
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
