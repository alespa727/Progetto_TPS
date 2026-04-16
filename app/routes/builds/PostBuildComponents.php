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
use Authorization\Authorization;
use OpenApi\Attributes as OA;

#[Route(Method::Post, ["api", "builds", "{buildId}:{int}", "components"], [], ContentTypes::Json)]
#[OA\Post(
    path: "/api/builds/{buildId}/components",
    tags: ["Builds"],
    summary: "Aggiunge un componente a un build specifico",
    parameters: [
        new OA\Parameter(
            name: "buildId",
            description: "ID del build",
            required: true,
            in: "path",
            schema: new OA\Schema(type: "integer")
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["component_id"],
            properties: [
                new OA\Property(
                    property: "component_id",
                    type: "integer",
                    example: 12
                )
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Componente aggiunto con successo"),
        new OA\Response(response: 409, description: "Conflitti di compatibilità"),
    ]
)]
class PostBuildComponents extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $build_id = $params->getInt("buildId");
        $component_id = $request->getBody("component_id");

        if (!isset($build_id))
            throw new BadRequest("build_id mancante");
        if (!isset($component_id))
            throw new BadRequest("component_id mancante");

        $db = Database::getDatabase();
        $username = Authorization::verify();

        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM builds WHERE id = ? AND user_id = ?");
        $stmt->execute([$build_id, $user["id"]]);

        $build = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($build)) {
            throw new BadRequest("build non trovata");
        }

        $stmt = $db->prepare("SELECT cr.*, cs_new.spec_value AS new_value, cs_existing.spec_value AS existing_value
                                FROM compatibility_rules cr
                                JOIN component_specs cs_new ON cs_new.spec_key = cr.spec_key AND cs_new.component_id = :component_id
                                JOIN component_specs cs_existing ON cs_existing.spec_key = cr.target_spec_key
                                JOIN components c_existing ON c_existing.id = cs_existing.component_id AND c_existing.category_id = cr.target_category_id
                                JOIN build_components bc ON bc.component_id = c_existing.id AND bc.build_id = :build_id;");

        $stmt->execute(["build_id" => $build_id, "component_id" => $component_id]);

        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $conflicts = [];
        foreach ($rules as $key => $rule) {
            $operator = $rule["operator"];
            $new_value = $rule["new_value"];
            $existing_value = $rule["existing_value"];

            $isCompatible = match ($operator) {
                "=" => $new_value === $existing_value,
                ">" => (float) $new_value > (float) $existing_value,
                "<" => (float) $new_value < (float) $existing_value,
                default => false,
            };

            if(!$isCompatible){
                $conflicts[]="Componente non compatibile: " . $new_value . " !" .$operator ." " . $existing_value;
            }
        }

        if(!empty($conflicts)){
            return Response::new()
                ->status(409)
                ->body(["errors"=>$conflicts]);
        }


        return Response::new()->ok()->body($rules);
    }
}
