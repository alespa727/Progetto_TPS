<?php

use Core\Exceptions\BadRequest;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use DatabaseUtil\Database;
use Authorization\Authorization;
use OpenApi\Attributes as OA;

#[Route(Method::Put, ["api", "builds", "{buildId}:{int}"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Put(
    path: "/api/builds/{buildId}",
    summary: "Sostituisce completamente una build",
    tags: ["Builds"],
    parameters: [
        new OA\Parameter(
            name: "buildId",
            description: "ID della build",
            required: true,
            in: "path",
            schema: new OA\Schema(type: "integer")
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "description"],
            properties: [
                new OA\Property(
                    property: "name",
                    type: "string",
                    example: "Default"
                ),
                new OA\Property(
                    property: "description",
                    type: "string",
                    example: "lunga descrizione"
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Build aggiornata"),
        new OA\Response(response: 403, description: "Forbidden"),
        new OA\Response(response: 404, description: "Build non trovata"),
    ]
)]
class PutBuilds extends Controller
{
    function validateBody(): array {
        return ["name", "description"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $buildId = $params->getInt("buildId");

        $name = $request->getBody("name");
        $description = $request->getBody("description");

        $db = Database::getDatabase();
       
        $user = Authorization::getUser();

        $stmt = $db->prepare("SELECT user_id FROM builds WHERE id = ?");
        $stmt->execute([$buildId]);
        $build = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$build) {
            return Response::new()->notFound();
        }

        if ($build["user_id"] != $user["id"]) {
            return Response::new()->forbidden();
        }

        $stmt = $db->prepare(
            "UPDATE builds SET name = ?, description = ? WHERE id = ?"
        );
        $stmt->execute([$name, $description, $buildId]);

        return Response::new()
            ->ok()
            ->body(["description" => "build aggiornata"]);
    }
}