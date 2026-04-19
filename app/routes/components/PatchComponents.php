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
use OpenApi\Attributes as OA;

#[Route(Method::Patch, ["api", "components", "{url_name}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Patch(
    path: "/api/components/{url_name}",
    summary: "Aggiorna parzialmente un componente",
    tags: ["Components"],
    parameters: [
        new OA\Parameter(
            name: "url_name",
            in: "path",
            required: true,
            schema: new OA\Schema(type: "string")
        )
    ],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "category", type: "string", example: "processori"),
                new OA\Property(property: "manufacturer", type: "string", example: "amd"),
                new OA\Property(property: "name", type: "string", example: "Ryzen 5600G"),
                new OA\Property(property: "description", type: "string", example: "CPU 6 core"),
                new OA\Property(property: "quantity", type: "integer", example: 1),
                new OA\Property(property: "price", type: "integer", example: 199),

                new OA\Property(
                    property: "specs",
                    type: "object",
                    additionalProperties: new OA\AdditionalProperties(
                        type: "string"
                    ),
                    example: [
                        "frequency" => "4.6GHz",
                        "cores" => "6"
                    ]
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Componente aggiornato con successo"
        ),
        new OA\Response(
            response: 400,
            description: "Richiesta non valida"
        ),
        new OA\Response(
            response: 404,
            description: "Componente non trovato"
        )
    ]
)]
class PatchComponents extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");

        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");
        $description = $request->getBody("description");
        $quantity = $request->getBody("quantity");
        $price = $request->getBody("price");
        $specs = $request->getBody("specs");

        if ($category === null && $manufacturer === null && $name === null &&
            $description === null && $quantity === null && $price === null && $specs === null) {
            throw new BadRequest("Nothing to update");
        }

        $db = \DatabaseUtil\Database::getDatabase();

        $stmt = $db->prepare("SELECT id, category_id FROM components WHERE url_name=?");
        $stmt->execute([$url_name]);
        $component = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$component) {
            throw new NotFound("Componente non esistente");
        }

        $id = $component["id"];
        $category_id = $component["category_id"];

        $fields = [];
        $values = [];

        if ($name !== null) {
            $fields[] = "name=?";
            $values[] = $name;

            $fields[] = "url_name=?";
            $values[] = str_replace(" ", "-", strtolower($name));
        }

        if ($description !== null) {
            $fields[] = "description=?";
            $values[] = $description;
        }

        if ($quantity !== null) {
            $fields[] = "quantity=?";
            $values[] = $quantity;
        }

        if ($price !== null) {
            $fields[] = "price=?";
            $values[] = $price;
        }

        if ($category !== null) {
            $stmt = $db->prepare("SELECT id FROM categories WHERE url_name=?");
            $stmt->execute([$category]);
            $category_id = $stmt->fetch(PDO::FETCH_ASSOC)["id"];

            $fields[] = "category_id=?";
            $values[] = $category_id;
        }

        if ($manufacturer !== null) {
            $stmt = $db->prepare("SELECT id FROM manufacturers WHERE url_name=?");
            $stmt->execute([$manufacturer]);
            $manufacturer_id = $stmt->fetch(PDO::FETCH_ASSOC)["id"];

            $fields[] = "manufacturer_id=?";
            $values[] = $manufacturer_id;
        }

        if (!empty($fields)) {
            $values[] = $id;
            $sql = "UPDATE components SET " . implode(",", $fields) . " WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        }

        if ($specs !== null) {

            $stmt = $db->prepare("DELETE FROM component_specs WHERE component_id=?");
            $stmt->execute([$id]);

            $prUnits = $db->prepare("SELECT spec_key, unit FROM category_specs WHERE category_id=?");
            $prUnits->execute([$category_id]);
            $units = array_column($prUnits->fetchAll(PDO::FETCH_ASSOC), 'unit', 'spec_key');

            $stmt = $db->prepare(
                "INSERT INTO component_specs (component_id, spec_key, spec_value, unit)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($specs as $key => $value) {
                $stmt->execute([$id, $key, $value, $units[$key] ?? ""]);
            }
        }

        return Response::new()->ok()->body(["message" => "Componente aggiornato"]);
    }
}