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
    summary: "Diminuisce la quantità di un componente di 1 ad una build specifica",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["component_id"],
            properties: [
                new OA\Property(
                    property: "component_name",
                    description: "url-name del componente",
                    type: "string",
                    example: 12
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
class ReduceBuildComponentQuantity extends Controller
{
    function manageRequest(Request $request, Params $params): Response{
        return Response::new();
    }
}