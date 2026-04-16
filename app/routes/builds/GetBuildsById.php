<?php

use Core\Exceptions\BadRequest;
use Core\Exceptions\Forbidden;
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

#[Route(Method::Get, ["api", "builds", "{buildId}:{int}"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Get(
    path: "/api/builds/{buildId}",
    summary: "Dettagli di una build",
    tags: ["Builds"],
    parameters: [
        new OA\Parameter(
            name: "buildId",
            description: "ID del build",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "OK",
            content: new OA\JsonContent(
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
                    ],
                type: "object"
            )
        ),
        new OA\Response(response: 403, description: "Forbidden"),
        new OA\Response(response: 404, description: "Build non trovata"),
    ]
)]
class GetBuildsById extends Controller
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

        if (!$build) {
            throw new NotFound("Build non trovata");
        }

        if ($build["user_id"] !== $user["id"]) {
            throw new Forbidden("Non hai il permesso di modificare questa risorsa");
        }

        $res = Response::new()
            ->ok()
            ->body($build);

        return $res;

    }
}
