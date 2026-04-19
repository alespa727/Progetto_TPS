<?php

use Core\Exceptions\BadRequest;
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

#[Route(Method::Patch, ["api", "rules", "{id}:{int}"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Patch(
    path: "/api/rules/{id}",
    summary: "Aggiorna parzialmente una regola",
    tags: ["Rules"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "integer")
        )
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "operator", type: "string", example: "="),
                new OA\Property(property: "required_value", type: "string", example: "true")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Regola aggiornata"),
        new OA\Response(response: 404, description: "Regola non trovata"),
        new OA\Response(response: 400, description: "Nessun dato da aggiornare")
    ]
)]
class PatchRule extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $id = $params->getInt("id");

        $operator = $request->getBody("operator");
        $required_value = $request->getBody("required_value");

        if ($operator === null && $required_value === null) {
            throw new BadRequest("Nothing to update");
        }

        $db = Database::getDatabase();

        $stmt = $db->prepare("SELECT id FROM compatibility_rules WHERE id=?");
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            throw new NotFound("Regola non trovata");
        }

        $fields = [];
        $values = [];

        if ($operator !== null) {
            $fields[] = "operator=?";
            $values[] = $operator;
        }

        if ($required_value !== null) {
            $fields[] = "required_value=?";
            $values[] = $required_value;
        }

        $values[] = $id;

        $sql = "UPDATE compatibility_rules SET " . implode(",", $fields) . " WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);

        return Response::new()
            ->ok()
            ->body(["message" => "Regola aggiornata"]);
    }
}