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


#[Route(Method::Post, ["api", "manufacturers"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Post(
    path: "/api/manufacturers",
    summary: "Crea un nuovo produttore",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name"],
            properties: [
                new OA\Property(
                    property: "name",
                    type: "string",
                    example: "ASUS"
                )
            ]
        )
    ),
    tags: ["Manufacturers"],
    responses: [
        new OA\Response(
            response: 201,
            description: "Produttore creato",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "description",
                        type: "string",
                        example: "creata nuovo marca"
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Errore richiesta"
        ),
        new OA\Response(
            response: 403,
            description: "Non autorizzato"
        )
    ]
)]
class PostManufacturers extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        
        $name = $request->getBody("name");
        if(!isset($name)){
            throw new BadRequest("Inserisci un nome nel body");
        }
        $nome_url = str_replace(" ", "-", strtolower($name));
        /**
         * @var PDO $db
         */
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("INSERT INTO manufacturers (name, url_name) values (?, ?)");

        $success = $pr->execute([$name, $nome_url]);
        if($success){
            $res = Response::new()
                ->created()
                ->body(["description"=>"creata nuovo marca"]);
        }else{
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    } 
}
