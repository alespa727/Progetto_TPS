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
#[Route(Method::Get, ["api", "components"], [], ContentTypes::Json)]
class GetAllComponents extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {

        $db = \DatabaseUtil\Database::getDatabase();

        $page = (int) ($request->getQuery('page') ?? 1);
        $limit = 50;

        if ($page <= 0) {
            throw new BadRequest("Pagina non esistente");

        }

        $offset = ($page - 1) * $limit;
        $pr = $db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.url_name,
                    c.description,
                    c.created_at,
                    c.quantity,
                    c.price,
                    cat.name AS category_name,
                    cat.url_name AS category_url,
                    m.name AS manufacturer_name,
                    m.url_name AS manufacturer_url,
                    (
                        SELECT JSON_ARRAYAGG(
                            JSON_OBJECT('key', cs.spec_key, 'value', cs.spec_value, 'label', cats.spec_label, 'unit', cs.unit)
                        )
                        FROM component_specs cs
                        INNER JOIN category_specs cats ON cats.category_id = cat.id AND cats.spec_key = cs.spec_key
                        WHERE cs.component_id = c.id
                    ) AS specs
                FROM components c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN manufacturers m ON c.manufacturer_id = m.id
                LIMIT :limit OFFSET :offset
            ");

        $pr->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $pr->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    
        $success = $pr->execute();
        $res = $pr->fetchAll(PDO::FETCH_ASSOC);


        foreach ($res as &$row) {
            $row['specs'] = isset($row['specs']) && is_string($row['specs']) && $row['specs'] !== ''
                ? json_decode($row['specs'], true) ?? []
                : [];
        }

        foreach ($res as $key => $c) {
            $res[$key]["image_url"] =
                "http://" . $_SERVER["HTTP_HOST"] . "/api/components/" . $c["url_name"] . "/image";
        }
        if ($success) {
            $resp = Response::new()
                ->ok()
                ->body($res);
        } else {
            throw new BadRequest("Error Processing Request");
        }
        return $resp;

    }
}
