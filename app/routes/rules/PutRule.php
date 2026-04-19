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

#[Route(Method::Put, ["api", "rules", "{id}:{int}"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Put(
    path: "/api/rules/{id}",
    summary: "Sostituisce completamente una regola",
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
        required: true,
        content: new OA\JsonContent(
            required: ["operator"],
            properties: [
                new OA\Property(property: "operator", type: "string", example: "="),
                new OA\Property(property: "required_value", type: "string", example: "true")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Regola aggiornata"),
        new OA\Response(response: 404, description: "Regola non trovata"),
        new OA\Response(response: 400, description: "Richiesta non valida")
    ]
)]
class PutRule extends Controller
{
    function validateBody(): array {
        return ["operator"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $id = $params->getInt("id");

        $operator = $request->getBody("operator");
        $required_value = $request->getBody("required_value");

        $db = Database::getDatabase();

        $stmt = $db->prepare("SELECT id FROM compatibility_rules WHERE id=?");
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            throw new NotFound("Regola non trovata");
        }

        $stmt = $db->prepare("
            UPDATE compatibility_rules 
            SET operator=?, required_value=?
            WHERE id=?
        ");

        $stmt->execute([
            $operator,
            $required_value,
            $id
        ]);

        return Response::new()
            ->ok()
            ->body(["message" => "Regola aggiornata"]);
    }
}