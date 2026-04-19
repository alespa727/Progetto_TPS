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

#[Route(Method::Patch, ["api", "builds", "{buildId}:{int}", "components"], [], ContentTypes::Json)]
#[OA\Patch(
    path: "/api/builds/{buildId}/components",
    summary: "Setta quantità",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["component_id", "quantity"],
            properties: [
                new OA\Property(
                    property: "component_name",
                    description: "url-name del componente",
                    type: "string",
                    example: "ryzen-5-7600g"
                ),
                new OA\Property(
                    property: "quantity",
                    description: "quantità",
                    type: "integer",
                    example: 1
                )
            ]
        )
    ),
    tags: ["Build-Components"],
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
        new OA\Response(response: 204, description: "Quantità ridotta con successo"),
        new OA\Response(response: 404, description: "Componente non presente"),
    ]
)]
class UpdateQuantityBuildComponent extends Controller
{
    function validateBody(): array
    {
        return ["component_name", "quantity"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $build_id = $params->getInt("buildId");
        $url_name = $request->getBody("component_name");
        $quantity = $request->getBody("quantity");

        $db = Database::getDatabase();
        $userId = Authorization::userId();

        $stmt = $db->prepare("SELECT * FROM builds WHERE id = ? AND user_id = ?");
        $stmt->execute([$build_id, $userId]);

        $build = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($build)) {
            throw new NotFound("Build non trovata");
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
                            WHERE bc.build_id = ? AND c.url_name=?;
                        ");
        $pr->execute([$build_id, $url_name]);
        $component = $pr->fetch(PDO::FETCH_ASSOC);

        if (empty($component)) {
            throw new BadRequest("Componente non trovato nella build");
        }

        $component_id = $component["id"];

        $pr = $db->prepare("SELECT name, max_per_build FROM categories WHERE id=?");
        $pr->execute([$component["category_id"]]);

        $category = $pr->fetch(PDO::FETCH_ASSOC);
        $name = $category["name"];
        $max_per_build = $category["max_per_build"];

        $component_id = $component["id"];

        $stmt = $db->prepare("
            SELECT quantity 
            FROM build_components 
            WHERE build_id = ? AND component_id = ?
        ");
        $stmt->execute([$build_id, $component_id]);

        if ($quantity > $max_per_build) {
            throw new BadRequest("Limite massimo di $name per build raggiunto");
        }

        $stmt = $db->prepare("
            UPDATE build_components 
            SET quantity = ?
            WHERE build_id = ? AND component_id = ?
        ");
        $stmt->execute([$quantity, $build_id, $component_id]);

        $stmt = $db->prepare("
            DELETE FROM build_components 
            WHERE build_id = ? AND component_id = ? AND quantity <= 0
        ");
        $stmt->execute([$build_id, $component_id]);

        if ($quantity <= 0) {
            return Response::new()
                ->ok()
                ->body(["description" => "Item rimosso dalla build con successo"]);
        }
        return Response::new()
            ->ok()
            ->body(["description" => "quantità aggiornata con successo a: $quantity"]);
    }
}