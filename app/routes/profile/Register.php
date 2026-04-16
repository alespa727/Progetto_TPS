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
            description: "Login riuscito, cookie JWT impostato",
            content: new OA\JsonContent(
                example: [
                    "message" => "Login effettuato con successo"
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
    function manageRequest(Request $request, Params $params): Response
    {
        $username = $request->getBody("username");
        $password = $request->getBody("password");

        /**
         * @var PDO $db
         */
        $db = \DatabaseUtil\Database::getDatabase();

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $pr = $db->prepare("INSERT INTO users (username, password_hash) values (?, ?)");
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $pr->execute([$username, $hash]);

        } catch (\Throwable $th) {
            $res = Response::new()
                    ->status(409)
                    ->body(["error"=>"Nome utente già utilizzato"]);
            return $res;
        } 
        $res = Response::new()
            ->created()
            ->body("Register riuscito");
        return $res; 
    }
}
