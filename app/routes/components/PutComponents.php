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

#[Route(Method::Put, ["api", "components", "{url_name}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Put(
    path: "/api/components/{url_name}",
    summary: "Aggiorna completamente un componente",
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
        required: true,
        content: new OA\JsonContent(
            required: ["category", "manufacturer", "name", "price"],
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
        new OA\Response(response: 200, description: "Componente aggiornato con successo"),
        new OA\Response(response: 400, description: "Richiesta non valida"),
        new OA\Response(response: 404, description: "Componente non trovato")
    ]
)]
class PutComponents extends Controller
{
    function validateBody(): array {
        return ["category", "manufacturer", "name", "price"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");

        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");
        $description = $request->getBody("description") ?? "";
        $quantity = $request->getBody("quantity") ?? 1;
        $price = $request->getBody("price");
        $specs = $request->getBody("specs") ?? [];

        if (!is_array($specs)) {
            throw new BadRequest("Specs deve essere un oggetto");
        }

        $db = \DatabaseUtil\Database::getDatabase();

        $stmt = $db->prepare("SELECT id FROM components WHERE url_name=?");
        $stmt->execute([$url_name]);
        $component = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$component) {
            throw new NotFound("Componente non esistente");
        }

        $id = $component["id"];

        $stmt = $db->prepare("SELECT id FROM categories WHERE url_name=?");
        $stmt->execute([$category]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new BadRequest("Categoria non valida");
        }

        $category_id = $row["id"];

        $stmt = $db->prepare("SELECT id FROM manufacturers WHERE url_name=?");
        $stmt->execute([$manufacturer]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new BadRequest("Manufacturer non valido");
        }

        $manufacturer_id = $row["id"];

        $new_url = str_replace(" ", "-", strtolower($name));

        $stmt = $db->prepare("
            UPDATE components 
            SET category_id=?, manufacturer_id=?, name=?, url_name=?, description=?, quantity=?, price=?
            WHERE id=?
        ");

        $stmt->execute([
            $category_id,
            $manufacturer_id,
            $name,
            $new_url,
            $description,
            $quantity,
            $price,
            $id
        ]);

        $stmt = $db->prepare("DELETE FROM component_specs WHERE component_id=?");
        $stmt->execute([$id]);

        $prUnits = $db->prepare("
            SELECT spec_key, unit 
            FROM category_specs 
            WHERE category_id=?
        ");
        $prUnits->execute([$category_id]);
        $units = array_column(
            $prUnits->fetchAll(PDO::FETCH_ASSOC),
            'unit',
            'spec_key'
        );

        $stmt = $db->prepare("
            INSERT INTO component_specs (component_id, spec_key, spec_value, unit)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($specs as $key => $value) {
            $stmt->execute([
                $id,
                $key,
                $value,
                $units[$key] ?? ""
            ]);
        }

        return Response::new()
            ->ok()
            ->body([
                "message" => "Componente aggiornato",
                "id" => $id
            ]);
    }
}