<?php

use Core\Exceptions\BadRequest;
use Core\Exceptions\Forbidden;
use Core\Exceptions\InternalServerError;
use Core\Exceptions\NotFound;
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

#[Route(Method::Delete, ["api", "builds", "{buildId}:{int}"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Delete(
    path: "/api/builds/{buildId}",
    summary: "Cancella una build",
    tags: ["Builds"],
    parameters: [
        new OA\Parameter(
            name: "buildId",
            description: "ID del build",
            required: true,
            in: "path",
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(response: 204, description: "Build cancellata con successo"),
        new OA\Response(response: 403, description: "Forbidden"),
        new OA\Response(response: 404, description: "Build non trovata"),
    ]
)]
class DeleteBuilds extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $id = $params->getInt("buildId");

        $db = Database::getDatabase();
        $username = Authorization::verify();

        $user = Authorization::getUser($username);


        $stmt = $db->prepare("SELECT id, user_id, name, description, status, is_public, total_price, created_at, updated_at FROM builds WHERE id=?");
        $stmt->execute([$id]);
        
        $build = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$build){
            throw new NotFound("Build non trovata");
        }

        if($build["user_id"]!==$user["id"]){
            throw new Forbidden("Non hai il permesso di modificare questa risorsa");
        }

        $stmt = $db->prepare("DELETE FROM builds WHERE id=?");
        $success = $stmt->execute([$id]);
        
        if($success){
            $res = Response::new()
            ->noContent();
        }else{
            throw new InternalServerError("Error Processing Request");
            
        }
        
        return $res;

    }
}
