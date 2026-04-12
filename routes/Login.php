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

#[Route(Method::Post, ["api", "login"], ContentTypes::Json)]
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
        $db = require_once __DIR__ . '/../database/Database.php';


        $pr = $db->prepare("SELECT id, username, password FROM users WHERE username=:username AND password=:password");
        $pr->execute(["username" => $username, "password" => $password]);

        $user = $pr->fetch(PDO::FETCH_ASSOC);

        if ($user) {
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
            ->body([]);
            return $res;
        } else {
            throw new BadRequest("Credenziali errate");
        }
    }
}
