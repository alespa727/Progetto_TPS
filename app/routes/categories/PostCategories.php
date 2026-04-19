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

#[Route(Method::Post, ["api", "categories"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
#[OA\Post(
    path: "/api/categories",
    summary: "Crea una nuova categoria con le sue specifiche",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "specs"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "CPU"),
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
                        type: "object"
                    )
                )
            ]
        )
    ),
    tags: ["Categories"],
    responses: [
        new OA\Response(response: 201, description: "Categoria creata con successo"),
        new OA\Response(response: 400, description: "Errore nella richiesta"),
    ]
)]
class PostCategories extends Controller
{
    function validateBody(): array {
        return ["name", "specs"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $nome_categoria = $request->getBody("name");
        $required_specified_specs = $request->getBody("specs");
        $max_per_build = $request->getBody("max_per_build") ?? 1;

        $nome_url = preg_replace('/[^a-z0-9]+/', '-', strtolower($nome_categoria));
        $nome_url = trim($nome_url, '-');

        /** @var PDO $db */
        $db = \DatabaseUtil\Database::getDatabase();

        $db->beginTransaction();
        try {
            $pr = $db->prepare("INSERT INTO categories (name, url_name, max_per_build) VALUES (?, ?, ?)");
            $pr->execute([$nome_categoria, $nome_url, $max_per_build]);
            $lastId = $db->lastInsertId();

            $pr = $db->prepare("INSERT INTO category_specs (category_id, spec_key, spec_label, unit) VALUES (?, ?, ?, ?)");
            foreach ($required_specified_specs as $spec) {
                $pr->execute([$lastId, $spec["key"], $spec["label"], $spec["unit"] ?? ""]);
            }

            $db->commit();
        } catch (\PDOException $e) {
            $db->rollBack();
            if ($e->getCode() === '23000') {
                throw new BadRequest("Categoria già esistente");
            }
            throw $e;
        }

        return Response::new()
            ->created()
            ->body(["description" => "creata nuova categoria"]);
    }
}