<?php

use Core\Exceptions\BadRequest;
use Core\Exceptions\Conflict;
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

#[Route(Method::Post, ["api", "components"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Tag(name: "Components")]
#[OA\PathItem(path: "/api/components")]
#[OA\Post(
    path: "/api/components",
    summary: "Crea un nuovo componente",
    tags: ["Components"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name"],
            properties: [
                new OA\Property(
                    property: "category",
                    description: "Category url-name",
                    type: "string",
                    example: "processori"
                ),
                new OA\Property(
                    property: "manufacturer",
                    description: "Manufacturer url-name",
                    type: "string",
                    example: "amd"
                ),
                new OA\Property(
                    property: "name",
                    description: "Nome del nuovo componente",
                    type: "string",
                    example: "Ryzen 5600G"
                ),
                new OA\Property(
                    property: "quantity",
                    type: "integer",
                    example: 1
                ),
                new OA\Property(
                    property: "price",
                    type: "integer",
                    example: 100
                ),
                new OA\Property(
                    property: "custom_specs",
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: "key",
                                type: "string",
                                example: "frequency"
                            ),
                            new OA\Property(
                                property: "value",
                                type: "string",
                                example: "5Ghz"
                            )
                        ]
                    )
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Componente creato con successo"),
        new OA\Response(response: 400, description: "Componente non esistente o errore nella richiesta"),
        new OA\Response(response: 403, description: "Non autorizzato"),
    ]
)]
class PostComponents extends Controller
{

    function validateRequest(Request $request, Params $params): bool
    {
        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");
        $price = $request->getBody("price");

        if (!isset($category) || !isset($manufacturer) || !isset($name) || !isset($price)) {
            return false;
        }

        return true;
    }


    function manageRequest(Request $request, Params $params): Response
    {
        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");
        $nome_url = str_replace(" ", "-", strtolower($name));
        $description = $request->getBody("description") ?? "";
        $quantity = $request->getBody("quantity") ?? 1;
        $price = $request->getBody("price");

        /**
         * @var array $specs
         */
        $specs = $request->getBody("specs");

        $db = \DatabaseUtil\Database::getDatabase();

        $pr = $db->prepare("SELECT id FROM categories WHERE url_name=?");
        $pr->execute([$category]);

        $category_id = $pr->fetch(PDO::FETCH_ASSOC)["id"];

        $pr = $db->prepare("SELECT id FROM manufacturers WHERE url_name=?");
        $pr->execute([$manufacturer]);

        $manufacturer_id = $pr->fetch(PDO::FETCH_ASSOC)["id"];

        try {
            $pr = $db->prepare("INSERT INTO components (category_id, manufacturer_id, name, url_name, description, quantity, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $success = $pr->execute([$category_id, $manufacturer_id, $name, $nome_url, $description, $quantity, $price]);
        } catch (PDOException $e) {
            
            if ($e->errorInfo[1] == 1062) {
                throw new Conflict("Componente duplicato");
            }
            
            throw $e;
        }
        
        
        $componentId = $db->lastInsertId();

        $pr = $db->prepare("INSERT INTO component_specs (component_id, spec_key, spec_value, unit) VALUES (:component_id, :spec_key, :spec_value, :unit)");

        $prUnits = $db->prepare("SELECT spec_key, unit FROM category_specs WHERE category_id = :category_id");
        $prUnits->execute(["category_id" => $category_id]);
        $units = array_column($prUnits->fetchAll(PDO::FETCH_ASSOC), 'unit', 'spec_key');

        if ($success) {

            foreach ($specs as $key => $spec) {
                $pr->execute([
                    "component_id" => $componentId,
                    "spec_key" => $key,
                    "spec_value" => $spec,
                    "unit" => $units[$key] ?? '',
                ]);
            }
            $res = Response::new()->created()->body(["id" => $componentId, "message" => "Componente creato"]);
            return $res;
        } else {
            throw new BadRequest("Componente non esistente");
        }
        return $res;

    }
}
