<?php

use Authorization\Authorization;
use Core\Exceptions\BadRequest;
use Core\Exceptions\Unauthorized;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OpenApi\Attributes as OA;

use DatabaseUtil\Database;

#[Route(Method::Get, ["api", "profile"], [], ContentTypes::Json)]
#[OA\Tag(name: "Profile")]
#[OA\PathItem(path: "/api/profile")]
#[OA\Get(
    path: "/api/profile",
    summary: "Vedi il tuo profilo",
    tags: ["Profile"],
    responses: [
        new OA\Response(
            response: 200,
            description: "OK",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "number", example: 1),
                    new OA\Property(property: "username", type: "string", example: "Ale"),
                    new OA\Property(property: "pfp_path", type: "string", example: "/idk.jpg"),
                    new OA\Property(property: "created_at", type: "string", example: "data"),
                     new OA\Property(property: "is_owner", type: "boolean", example: true),

                ]
            )
        )
    ]
)]
class Profile extends Controller
{
    private $key = 'example_key_of_sufficient_length';


    function manageRequest(Request $request, Params $params): Response
    {

        $user = Authorization::getUser();

        $res = Response::new()
            ->ok()
            ->body($user);

        return $res;

    }
}
