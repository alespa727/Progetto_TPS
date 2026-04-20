<?php

use Core\Exceptions\NotFound;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use DatabaseUtil\Database;
use OpenApi\Attributes as OA;

#[Route(Method::Get, ["api", "rules", "{id}:{int}"], [], ContentTypes::Json)]
#[OA\Get(
    path: "/api/rules/{id}",
    summary: "Dettaglio regola di compatibilità",
    tags: ["Rules"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer")
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Regola trovata",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "category_id", type: "integer"),
                    new OA\Property(property: "target_category_id", type: "integer"),
                    new OA\Property(property: "spec_key", type: "string"),
                    new OA\Property(property: "target_spec_key", type: "string"),
                    new OA\Property(property: "operator", type: "string"),
                    new OA\Property(property: "required_value", type: "string", nullable: true)
                ],
                type: "object"
            )
        ),
        new OA\Response(response: 404, description: "Regola non trovata")
    ]
)]
class GetRule extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $id = $params->getInt("id");

        $db = Database::getDatabase();

        $stmt = $db->prepare("SELECT * FROM compatibility_rules WHERE id=?");
        $stmt->execute([$id]);

        $rule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rule) {
            throw new NotFound("Regola non trovata");
        }

        return Response::new()
            ->ok()
            ->body($rule);
    }
}