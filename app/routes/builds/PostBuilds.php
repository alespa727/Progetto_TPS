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

#[Route(Method::Post, ["api", "builds"], [AuthMiddleware::class], ContentTypes::Json)]
#[OA\Tag(name: "Builds")]
#[OA\PathItem(path: "/api/builds")]
#[OA\Post(
    path: "/api/builds",
    summary: "Crea una nuova build per il tuo account",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "description"],
            properties: [
                new OA\Property(
                    property: "name",
                    type: "string",
                    example: "Default"
                ),
                new OA\Property(
                    property: "description",
                    type: "string",
                    example: "lunga descrizione"
                )
            ]
        )
    ),
    tags: ["Builds"],
    responses: [
        new OA\Response(response: 201, description: "Build creata con successo"),
    ]
)]
class PostBuilds extends Controller
{

    function validateBody(): array{
        return ["name", "description"];
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $name = $request->getBody("name");
        $description = $request->getBody("description");

        $db = Database::getDatabase();
        $username = Authorization::verify();


        $stmt = $db->prepare("SELECT id, username, pfp_hash, created_at, is_owner FROM users WHERE username=:username");
        $stmt->execute(["username" => $username]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("INSERT INTO builds (user_id, name, description) values (?, ?, ?)");
        $stmt->execute([$user["id"], $name ?? "Default", $description ?? ""]);
        
        $res = Response::new()
            ->created()
            ->body(["description" => "build creata con successo"]);


        return $res;

    }
}
