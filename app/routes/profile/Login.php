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

#[Route(Method::Post, ["api", "login"], [], ContentTypes::Json)]
#[OA\Tag(name: "Profile")]
#[OA\PathItem(path: "/api/login")]
#[OA\Post(
    path: "/api/login",
    summary: "Fai il login",
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
        new OA\Response(response: 200, description: "Login riuscito, cookie JWT impostato")
    ]
)]
class Login extends Controller
{
    private $key = 'example_key_of_sufficient_length';

    function validateBody(): array {
        return ["username", "password"];
    }


    function manageRequest(Request $request, Params $params): Response
    {
        $username = $request->getBody("username");
        $password = $request->getBody("password");

        /**
         * @var PDO $db
         */
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("SELECT username, password_hash FROM users WHERE username=:username");

        $pr->execute(["username" => $username]);
        $user = $pr->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password_hash"])) {

            $jwt = JWT::encode(["username" => $user["username"]], $this->key, 'HS256');
            setcookie(
                "token", 
                $jwt,
                [
                    "expires" => time() + 3600,
                    "path" => "/",
                    "httponly" => true,
                    "secure" => false,
                    "samesite" => "Lax"
                ]
            );
            $res = Response::new()
                ->ok()
                ->body([
                    'description' => 'Login riuscito, cookie JWT impostato',
                ]);
            return $res;

        } else {
            throw new BadRequest("Credenziali sbagliate o utente non trovato");
        }

    } 
}
