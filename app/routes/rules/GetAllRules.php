<?php

use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use DatabaseUtil\Database;
use OpenApi\Attributes as OA;

#[Route(Method::Get, ["api", "rules"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Get(
    path: "/api/rules",
    summary: "Lista tutte le regole di compatibilità",
    tags: ["Rules"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Lista regole",
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
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
            )
        )
    ]
)]
class GetAllRules extends Controller
{
    function manageRequest(Request $request, $params): Response
    {
        $db = Database::getDatabase();

        $stmt = $db->prepare("SELECT * FROM compatibility_rules");
        $stmt->execute();

        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::new()
            ->ok()
            ->body($rules);
    }
}