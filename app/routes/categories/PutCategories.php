<?php

use Core\Exceptions\NotFound;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use OpenApi\Attributes as OA;

#[Route(Method::Put, ["api", "categories", "{url_name}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Put(
    path: "/api/categories/{url_name}",
    summary: "Sostituisce completamente una categoria",
    tags: ["Categories"],
    parameters: [
        new OA\Parameter(
            name: "url_name",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string")
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "specs"],
            properties: [
                new OA\Property(property: "name", type: "string"),
                new OA\Property(
                    property: "specs",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "key", type: "string"),
                            new OA\Property(property: "label", type: "string"),
                            new OA\Property(property: "unit", type: "string")
                        ]
                    )
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Categoria aggiornata"),
        new OA\Response(response: 404, description: "Categoria non trovata")
    ]
)]
class PutCategories extends Controller
{
    function validateBody(): array {
        return ["name", "specs"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");

        $name = $request->getBody("name");
        $specs = $request->getBody("specs");

        $db = \DatabaseUtil\Database::getDatabase();

        $stmt = $db->prepare("SELECT id FROM categories WHERE url_name = ?");
        $stmt->execute([$url_name]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            throw new NotFound("Categoria non trovata");
        }

        $categoryId = $category["id"];

        $new_url = str_replace(" ", "-", strtolower($name));

        $stmt = $db->prepare(
            "UPDATE categories SET name = ?, url_name = ? WHERE id = ?"
        );
        $stmt->execute([$name, $new_url, $categoryId]);

        $stmt = $db->prepare("DELETE FROM category_specs WHERE category_id = ?");
        $stmt->execute([$categoryId]);

        $stmt = $db->prepare(
            "INSERT INTO category_specs (category_id, spec_key, spec_label, unit)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($specs as $spec) {
            $stmt->execute([
                $categoryId,
                $spec["key"],
                $spec["label"],
                $spec["unit"] ?? ""
            ]);
        }

        return Response::new()
            ->ok()
            ->body(["description" => "categoria aggiornata"]);
    }
}