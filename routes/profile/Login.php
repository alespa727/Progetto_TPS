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
#[OA\Post(
    path: "/api/login",
    summary: "Fai il login",
    parameters: [
        new OA\Parameter(
            name: "username",
            required: false,
            schema: new OA\Schema(type: "string")
        ),
        new OA\Parameter(
            name: "password",
            required: false,
            schema: new OA\Schema(type: "string")
        ) 

    ], 
    responses: [
        new OA\Response(response: 200, description: 'Login riuscito, cookie JWT impostato',)
    ]
)]
class Login extends Controller
{
    private $key = 'example_key_of_sufficient_length';


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

        $user_password = $user["password_hash"];

        if ($user && password_verify($password, $user_password)) {

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
