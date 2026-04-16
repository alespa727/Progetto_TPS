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
use OpenApi\Attributes as OA;


#[OA\Get(
    path: "/api/builds",
    tags: ["Builds"],
    summary: "Lista delle build dell'utente loggato",
    responses: [
        new OA\Response(
            response: 200,
            description: "Lista build",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "id", type: "integer"),
                        new OA\Property(property: "user_id", type: "integer"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "status", type: "string"),
                        new OA\Property(property: "is_public", type: "boolean"),
                        new OA\Property(property: "total_price", type: "integer", nullable: true),
                        new OA\Property(property: "created_at", type: "string"),
                        new OA\Property(property: "updated_at", type: "string", nullable: true)
                    ]
                )
            )
        ),
        new OA\Response(
            response: 401,
            description: "Non autorizzato"
        )
    ]
)]
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
