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
use Core\ApiDoc;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OpenApi\Attributes as OA;

#[Route(Method::Get, ["api", "categories", "{url_name}:{string}"], [], ContentTypes::Json)]
#[OA\Get(
    path: "/api/categories/{url_name}",
    summary: "Dettagli categoria",
    tags: ["Categories"],
    parameters: [
        new OA\Parameter(
            name: "url_name",
            description: "url-name della categoria",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string")
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "OK",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "name", type: "string", example: "CPU"),
                    new OA\Property(property: "url_name", type: "string", example: "cpu"),
                    new OA\Property(property: "max_per_build", type: "integer", example: 1),
                    new OA\Property(
                        property: "specs",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "key", type: "string"),
                                new OA\Property(property: "label", type: "string"),
                                new OA\Property(property: "unit", type: "string")
                            ],
                            type: "object",
                        )
                    )
                ],
                type: "object"
            )
        ),
        new OA\Response(
            response: 404,
            description: "Categoria non esistente"
        )
    ]
)]
class GetCategories extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");

        $result = preg_replace('/\s+/', '', $url_name);

        if ($result === "") {
            throw new BadRequest("Dare una categoria valida");
        }

        $db = \DatabaseUtil\Database::getDatabase();

        $pr = $db->prepare("
                        SELECT 
                            C.id,
                            C.name,
                            C.url_name,
                            C.max_per_build,
                            CS.spec_key,
                            CS.spec_label,
                            CS.unit
                        FROM categories AS C
                        LEFT JOIN category_specs AS CS 
                            ON CS.category_id = C.id
                        WHERE C.url_name = ?
                    ");

        $success = $pr->execute([$url_name]);
        $rows = $pr->fetchAll(PDO::FETCH_ASSOC);

        if (!$success || empty($rows)) {
            throw new NotFound("Categoria non esistente");
        }

        $category = [
            "id" => $rows[0]["id"],
            "name" => $rows[0]["name"],
            "url_name" => $rows[0]["url_name"],
            "specs" => []
        ];

        foreach ($rows as $row) {
            if ($row["spec_key"] !== null) {
                $category["specs"][] = [
                    "key" => $row["spec_key"],
                    "label" => $row["spec_label"],
                    "unit" => $row["unit"]
                ];
            }
        }

        return Response::new()
            ->ok()
            ->body($category);
    }
}
