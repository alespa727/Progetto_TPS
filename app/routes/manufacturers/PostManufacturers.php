<?php

use Core\Exceptions\BadRequest;
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


#[Route(Method::Post, ["api", "manufacturers"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Post(
    path: "/api/manufacturers",
    summary: "Crea un nuovo produttore",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name"],
            properties: [
                new OA\Property(
                    property: "name",
                    type: "string",
                    example: "ASUS"
                )
            ]
        )
    ),
    tags: ["Manufacturers"],
    responses: [
        new OA\Response(
            response: 201,
            description: "Produttore creato",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "description",
                        type: "string",
                        example: "creata nuovo marca"
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Errore richiesta"
        ),
        new OA\Response(
            response: 403,
            description: "Non autorizzato"
        )
    ]
)]
class PostManufacturers extends Controller
{
    function validateBody(): array {
        return ["name"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $name = $request->getBody("name");

        if (empty(trim($name))) {
            throw new BadRequest("Il nome non può essere vuoto");
        }

        $nome_url = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $nome_url = trim($nome_url, '-');

        /** @var PDO $db */
        $db = \DatabaseUtil\Database::getDatabase();

        try {
            $pr = $db->prepare("INSERT INTO manufacturers (name, url_name) VALUES (?, ?)");
            $pr->execute([$name, $nome_url]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new BadRequest("Produttore già esistente");
            }
            throw $e;
        }

        return Response::new()
            ->created()
            ->body(["description" => "creato nuovo produttore"]);
    }
}