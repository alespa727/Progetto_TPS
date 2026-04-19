<?php

use Core\Exceptions\BadRequest;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes as OA;

#[Route(Method::Post, ["api", "register"], [], ContentTypes::Json)]
#[OA\Tag(name: "Profile")]
#[OA\PathItem(path: "/api/register")]
#[OA\Post(
    path: "/api/register",
    summary: "Fai il register",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "username", type: "string"),
                new OA\Property(property: "password", type: "string"),
            ]
        )
    ),
    tags: ["Profile"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Creato nuovo account",
            content: new OA\JsonContent(
                example: [
                    "description" => "Creazione nuovo account"
                ]
            )
        ),
        new OA\Response(
            response: 409,
            description: "Nome utente già utilizzato",
            content: new OA\JsonContent(
                example: ["error" => "Nome utente già utilizzato"]
            )
        )
    ]
)]
class Register extends Controller
{

    function validateBody(): array {
        return ["username", "password"];
    }


    function manageRequest(Request $request, Params $params): Response
    {

        $username = $request->getBody("username");
        if(strlen($username) < 4){
            throw new BadRequest("Inserisci uno username valido");
            
        }
        $password = $request->getBody("password");

        /**
         * @var PDO $db
         */
        $db = \DatabaseUtil\Database::getDatabase();

        // Controllo se è il primo utente inserito (quindi l'owner)
        $countStmt = $db->query("SELECT COUNT(*) FROM users");
        $userCount = (int) $countStmt->fetchColumn();
        $isOwner = $userCount === 0 ? 1 : 0;

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $pr = $db->prepare("INSERT INTO users (username, password_hash, is_owner) values (?, ?, ?)");
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $pr->execute([$username, $hash, $isOwner]);

        } catch (\Throwable $th) {
            $res = Response::new()
                    ->status(409)
                    ->body(["error"=>"Nome utente già utilizzato"]);
            return $res;
        } 

        if($isOwner===1){
            return Response::new()
                ->created()
                ->body(["description"=>"Creato nuovo account owner"]);; 
        }
        
        return Response::new()
            ->created()
            ->body(["description"=>"Register riuscito"]);
    }
}
