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

#[Route(Method::Delete, ["api", "rules", "{id}:{int}"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Delete(
    path: "/api/rules/{id}",
    summary: "Elimina una regola di compatibilità",
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
        new OA\Response(response: 200, description: "Regola eliminata"),
        new OA\Response(response: 404, description: "Regola non trovata")
    ]
)]
class DeleteRule extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $id = $params->getInt("id");
        $db = Database::getDatabase();

        $stmt = $db->prepare("SELECT id FROM compatibility_rules WHERE id=?");
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            throw new NotFound("Regola non trovata");
        }

        $stmt = $db->prepare("DELETE FROM compatibility_rules WHERE id=?");
        $stmt->execute([$id]);

        return Response::new()
            ->ok()
            ->body(["message" => "Regola eliminata"]);
    }
}