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

#[Route(Method::Get, ["api", "components", "{url_name}:{string}"], [], ContentTypes::Json)]
#[OA\Get(
    path: "/api/components/{url_name}",
    summary: "Dettaglio componente",
    tags: ["Components"],
    parameters: [
        new OA\Parameter(
            name: "url_name",
            description: "url-name del componente",
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
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "url_name", type: "string"),
                    new OA\Property(property: "price", type: "integer", nullable: true),
                    new OA\Property(property: "description", type: "string", nullable: true)
                ],
                type: "object"
            )
        ),
        new OA\Response(
            response: 404,
            description: "Componente non esistente"
        )
    ]
)]
class GetComponents extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");
        $db = \DatabaseUtil\Database::getDatabase();


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
                            JSON_OBJECT('key', cs.spec_key, 'value', cs.spec_value, 'unit', cs.unit)
                        )
                        FROM component_specs cs
                        WHERE cs.component_id = c.id
                    ) AS specs
                FROM components c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN manufacturers m ON c.manufacturer_id = m.id
                WHERE c.url_name = ?
            ");

        
         
        $success = $pr->execute([$url_name]);
        $component = $pr->fetch(PDO::FETCH_ASSOC);

        $component['specs'] = is_string($component['specs']) && $component['specs'] !== ''
            ? json_decode($component['specs'], true) ?? []
            : [];
            
        if ($success && $component) {
            $component["image_url"] =
                "http://" . $_SERVER["HTTP_HOST"] . "/api/components/" . $component["url_name"] . "/image";

            $res = Response::new()
                ->ok()
                ->body($component);
        } else {
            throw new NotFound("Componente non esistente");
        }
        return $res;

    }
}
