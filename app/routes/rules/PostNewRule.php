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
use DatabaseUtil\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OpenApi\Attributes as OA;

#[Route(Method::Post, ["api", "rules"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Tag(name: "Rules")]
#[OA\PathItem(path: "/api/rules")]
#[OA\Post(
    path: "/api/rules",
    summary: "Crea una nuova regola di compatibilità",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                "category1_id",
                "category2_id",
                "spec_key1",
                "spec_key2",
                "operator"
            ],
            properties: [
                new OA\Property(property: "category1_id", type: "string"),
                new OA\Property(property: "category2_id", type: "string"),
                new OA\Property(property: "spec_key1", type: "string"),
                new OA\Property(property: "spec_key2", type: "string"),
                new OA\Property(property: "operator", type: "string"),
            ]
        )
    ),
    tags: ["Rules"],
    responses: [
        new OA\Response(response: 201, description: "Regola creata con successo"),
    ]
)]
class PostNewRule extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $db = Database::getDatabase();
        $id1 = $request->getBody("category1_id");
        $id2 = $request->getBody("category2_id");

        if (!$id1) {
            throw new BadRequest("Inserisci il primo id nel body");
        }
        if (!$id2) {
            throw new BadRequest("Inserisci il secondo id nel body");
        }

        $spec_key1 = $request->getBody("spec_key1");
        $spec_key2 = $request->getBody("spec_key2");

        $stmt = $db->prepare("SELECT * FROM category_specs WHERE category_id=? AND spec_key=?");
        $stmt->execute([$id1, $spec_key1]);

        $spec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($spec)) {
            throw new BadRequest("Inserisci una spec1 valida");
        }

        $stmt = $db->prepare("SELECT * FROM category_specs WHERE category_id=? AND spec_key=?");
        $stmt->execute([$id2, $spec_key2]);

        $spec = $stmt->fetch(PDO::FETCH_ASSOC);
        $spec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($spec)) {
            throw new BadRequest("Inserisci una spec2 valida");
        }

        $operator = $request->getBody("operator");

        $pr = $db->prepare("INSERT INTO compatibility_rules (category_id, target_category_id, spec_key, target_spec_key, operator, required_value) values (:category_id, :target_category_id, :spec_key, :target_spec_key, :operator, :required_value)");

        $params = [
            "category_id" => $id1,
            "target_category_id" => $id2,
            "spec_key" => $spec_key1,
            "target_spec_key" => $spec_key2,
            "operator" => $operator,
            "required_value" => true
        ];
        $pr->execute($params);

        $operator = match ($operator) {
            "<" => ">",
            ">" => "<",
            default => $operator,
        };

        $params_inverted = [
            "category_id" => $id2,
            "target_category_id" => $id1,
            "spec_key" => $spec_key2,
            "target_spec_key" => $spec_key1,
            "operator" => $operator,
            "required_value" => true
        ];
        $pr->execute($params_inverted);

        $res = Response::new()
            ->created()
            ->body(["rule1" => $params, "rule2" => $params_inverted]);
        return $res;

    }
}
