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
use DatabaseUtil\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Authorization\Authorization;

#[Route(Method::Post, ["api", "builds"], [AuthMiddleware::class], ContentTypes::Json)]
class PostBuilds extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $name = $request->getBody("name");
        $description = $request->getBody("description");


        $db = Database::getDatabase();
        $username = Authorization::verify();


        $stmt = $db->prepare("SELECT id, username, pfp_path, created_at, is_owner FROM users WHERE username=:username");
        $stmt->execute(["username" => $username]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("INSERT INTO builds (user_id, name, description) values (?, ?, ?)");
        $stmt->execute([$user["id"], $name ?? "Default", $description ?? ""]);
        
        $res = Response::new()
            ->created()
            ->body(["description" => "build creata con successo"]);


        return $res;

    }
}
