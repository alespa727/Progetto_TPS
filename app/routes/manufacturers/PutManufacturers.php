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

#[Route(Method::Put, ["api", "manufacturers", "{url_name}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Put(
    path: "/api/manufacturers/{url_name}",
    summary: "Sostituisce completamente un produttore",
    tags: ["Manufacturers"],
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
            required: ["name"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "ASUS")
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Produttore aggiornato"),
        new OA\Response(response: 404, description: "Produttore non trovato"),
        new OA\Response(response: 400, description: "Richiesta non valida")
    ]
)]
class PutManufacturers extends Controller
{
    function validateBody(): array {
        return ["name"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");
        $name = $request->getBody("name");

        $db = \DatabaseUtil\Database::getDatabase();

        $stmt = $db->prepare("SELECT id FROM manufacturers WHERE url_name=?");
        $stmt->execute([$url_name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new NotFound("Produttore non trovato");
        }

        $id = $row["id"];
        $new_url = str_replace(" ", "-", strtolower($name));

        $stmt = $db->prepare("UPDATE manufacturers SET name=?, url_name=? WHERE id=?");
        $stmt->execute([$name, $new_url, $id]);

        return Response::new()
            ->ok()
            ->body(["message" => "Produttore aggiornato"]);
    }
}