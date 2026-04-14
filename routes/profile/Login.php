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

#[Route(Method::Post, ["api", "login"], [], ContentTypes::Json)]
#[ApiDoc(
    summary: 'Login utente',
    description: 'Autentica un utente tramite username e password. In caso di successo imposta un cookie JWT HttpOnly valido per 1 ora.',
    request: [
        'username' => 'string',
        'password' => 'string',
    ],
    responses: [
        200 => [
            'description' => 'Login riuscito, cookie JWT impostato',
        ],
        400 => [
            'description' => 'Credenziali errate o utente non trovato',
        ],
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
