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

#[Route(Method::Get, ["api", "builds", "{buildId}:{int}", "components"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Get(
    path: "/api/builds/{buildId}/components",
    summary: "Dettagli di una build",
    tags: ["Build-Components"],
    parameters: [
        new OA\Parameter(
            name: "buildId",
            description: "ID del build",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer")
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "OK",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "id", type: "integer"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "url_name", type: "string"),
                        new OA\Property(property: "description", type: "string", nullable: true),
                        new OA\Property(property: "price", type: "integer"),
                        new OA\Property(property: "quantity", type: "integer")
                    ]
                )
            )
        ),
        new OA\Response(response: 403, description: "Forbidden"),
        new OA\Response(response: 404, description: "Build non trovata"),
    ]
)]
class GetBuildComponents extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $buildId = $params->getInt("buildId");

        $db = Database::getDatabase();
        $username = Authorization::verify();

        $user = Authorization::getUser();

        $stmt = $db->prepare("SELECT id, user_id, name, description, status, is_public, total_price, created_at, updated_at FROM builds WHERE id=?");
        $stmt->execute([$buildId]);

        $build = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$build) {
            throw new NotFound("Build non trovata");
        }

        if ($build["user_id"] !== $user["id"]) {
            throw new Forbidden("Non hai il permesso di accedere a questa risorsa");
        }
        $pr = $db->prepare("
                            SELECT 
                                c.id,
                                c.name,
                                c.url_name,
                                c.description,
                                c.created_at,
                                c.price AS price_per_item,
                                bc.quantity,
                                (c.price * bc.quantity) AS total_price,
                                cat.name AS category_name,
                                cat.url_name AS category_url,
                                m.name AS manufacturer_name,
                                m.url_name AS manufacturer_url
                            FROM build_components bc
                            INNER JOIN components c ON c.id = bc.component_id
                            LEFT JOIN categories cat ON c.category_id = cat.id
                            LEFT JOIN manufacturers m ON c.manufacturer_id = m.id
                            WHERE bc.build_id = ?
                        ");
        $pr->execute([$buildId]);

        $components = $pr->fetchAll(PDO::FETCH_ASSOC);

        foreach ($components as $key => $c) {
            $components[$key]["image_url"] =
                "http://" . $_SERVER["HTTP_HOST"] . "/api/components/" . $c["url_name"] . "/image";
        }

        return Response::new()
            ->ok()
            ->body($components);

    }
}