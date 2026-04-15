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

#[Route(Method::Get, ["api", "builds"], [AuthMiddleware::class], ContentTypes::Json)]
class GetMyBuilds extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
      
        $db = Database::getDatabase();
        $username = Authorization::verify();


        $stmt = $db->prepare("SELECT id, username, pfp_path, created_at, is_owner FROM users WHERE username=:username");
        $stmt->execute(["username" => $username]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT id, user_id, name, description, status, is_public, total_price, created_at, updated_at FROM builds WHERE user_id=?");
        $stmt->execute([$user["id"]]);
        
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $res = Response::new()
            ->ok()
            ->body($list);


        return $res;

    }
}
