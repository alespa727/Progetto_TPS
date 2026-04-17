<?php
use Core\Response;
use OpenApi\Attributes as OA;
use Core\Controller;

#[OA\Patch(
    path: "/api/builds/{buildId}",
    summary: "Modifica una build",
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
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
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
        new OA\Response(response: 204, description: "Build modificata con successo"),
        new OA\Response(response: 403, description: "Forbidden"),
        new OA\Response(response: 404, description: "Build non trovata"),
    ]
)]
class PatchBuilds extends Controller
{
    function manageRequest(Core\Request $request, Core\Params $params): Core\Response{
        return Response::new()->ok()->body([]);
    }
}