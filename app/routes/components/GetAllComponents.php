<?php

use Core\Exceptions\BadRequest;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/api/components",
    summary: "Lista componenti",
    tags: ["Components"],
    parameters: [
        new OA\Parameter(
            name: "page",
            description: "Pagina (default 1)",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "integer", example: 1)
        )
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
                        new OA\Property(property: "price", type: "integer", nullable: true)
                    ]
                )
            )
        ),
        new OA\Response(response: 401, description: "Unauthorized")
    ]
)]
#[Route(Method::Get, ["api", "components"], [AuthMiddleware::class], ContentTypes::Json)]
class GetAllComponents extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {

        $db = \DatabaseUtil\Database::getDatabase();
        
        $page = (int) ($request->getQuery('page') ?? 1);
        $limit = 50;

        $offset = ($page - 1) * $limit;

        $pr = $db->prepare("SELECT * FROM components LIMIT $limit OFFSET $offset");

        $success = $pr->execute();
        $res = $pr->fetchAll(PDO::FETCH_ASSOC);

        if ($success) {
            $res = Response::new()
                ->ok()
                ->body($res);
        } else {
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    }
}
