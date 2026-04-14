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

#[Route(Method::Post, ["api", "register"], [], ContentTypes::Json)]
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
            throw new BadRequest("Nome utente già utilizzato");
        } 
        $res = Response::new()
            ->ok()
            ->body([]);
        return $res; 
    }
}
