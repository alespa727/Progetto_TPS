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
#[OA\Tag(name: "Categories")]
#[OA\PathItem(path: "/api/categories")]
#[OA\Post(
    path: "/api/categories",
    summary: "Crea una nuova categoria con le sue specifiche",
    tags: ["Categories"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name"],
            properties: [
                "name" => new OA\Schema(
                    type: "string",
                    example: "CPU"
                ),
                "specs" => new OA\Schema(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            "key" => new OA\Schema(type: "string"),
                            "label" => new OA\Schema(type: "string"),
                            "unit" => new OA\Schema(type: "string")
                        ]
                    )
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Categoria creata con successo"),
        new OA\Response(response: 400, description: "Errore nella richiesta"),
    ]
)]
class PostCategories extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {

        $nome_categoria = $request->getBody("name");
        /**
         * @var array
         */
        $required_specified_specs = $request->getBody("specs");

        if (!isset($nome_categoria)) {
            throw new BadRequest("Inserisci un nome nel body");
        }
        $nome_url = str_replace(" ", "-", strtolower($nome_categoria));
        /**
         * @var PDO $db
         */
        $db = \DatabaseUtil\Database::getDatabase();

        $pr = $db->prepare("INSERT INTO categories (name, url_name) values (?, ?)");
        $success = $pr->execute([$nome_categoria, $nome_url]);
        $lastId = $db->lastInsertId();

        $pr = $db->prepare("INSERT INTO category_specs (category_id, spec_key, spec_label, unit) values (?, ?, ?, ?)");

        if ($success) {

            foreach ($required_specified_specs as $key => $spec) {
                $pr->execute([$lastId, $spec["key"], $spec["label"], $spec["unit"] ?? ""]);
            }

            $res = Response::new()
                ->created()
                ->body(["description" => "creata nuova categoria"]);
        } else {
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    }
}
