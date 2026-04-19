<?php

use Core\Exceptions\BadRequest;
use Core\Exceptions\Unauthorized;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OpenApi\Attributes as OA;

use DatabaseUtil\Database;

#[Route(Method::Get, ["api", "profile"], [], ContentTypes::Json)]
#[OA\Tag(name: "Profile")]
#[OA\PathItem(path: "/api/profile")]
#[OA\Get(
    path: "/api/profile",
    summary: "Vedi il tuo profilo",
    tags: ["Profile"],
    responses: [
        new OA\Response(
            response: 200,
            description: "OK",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "number", example: 1),
                    new OA\Property(property: "username", type: "string", example: "Ale"),
                    new OA\Property(property: "pfp_path", type: "string", example: "/idk.jpg"),
                    new OA\Property(property: "created_at", type: "string", example: "data"),
                     new OA\Property(property: "is_owner", type: "boolean", example: true),

                ]
            )
        )
    ]
)]
class Profile extends Controller
{
    private $key = 'example_key_of_sufficient_length';


    function manageRequest(Request $request, Params $params): Response
    {

        /**
         * @var PDO $db
         */
        $db = Database::getDatabase();
        if (!array_key_exists("token", $_COOKIE))
            throw new Unauthorized("Esegui il login");

        $cookie_jwt = $_COOKIE["token"];
        $decoded = JWT::decode($cookie_jwt, new Key($this->key, 'HS256'), $headers);
        $decoded_array = (array) $decoded;

        $pr = $db->prepare("SELECT id, username, pfp_hash, created_at, is_owner FROM users WHERE username=:username");

        $pr->execute(["username" => $decoded_array["username"]]);
        $user = $pr->fetch(PDO::FETCH_ASSOC);


        $res = Response::new()
            ->ok()
            ->body($user);

        return $res;

    }
}
