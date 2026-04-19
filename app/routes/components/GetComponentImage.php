<?php

use Core\Exceptions\BadRequest;
use Core\Exceptions\NotFound;
use Core\FileHandler;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use OpenApi\Attributes as OA;

#[Route(Method::Get, ["api", "components", "{url_name}:{string}", "image"], [], ContentTypes::InlineFile)]

#[OA\Get(
    path: "/api/components/{url_name}/image",
    summary: "Download immagine componente",
    tags: ["Component-Images"],
    description: "Restituisce immagine inline",
    security: [],
    parameters: [
        new OA\Parameter(
            name: "url_name",
            in: "path",
            required: true,
            description: "Slug del componente",
            schema: new OA\Schema(type: "string")
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "OK",
        ),
        new OA\Response(
            response: 404,
            description: "Componente non trovato"
        ),
        new OA\Response(
            response: 400,
            description: "File non valido o mancante"
        )
    ]
)]
class GetComponentImage extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");

        $db = \DatabaseUtil\Database::getDatabase();

        $stmt = $db->prepare("SELECT id, image_hash FROM components WHERE url_name=?");
        $stmt->execute([$url_name]);
        $component = $stmt->fetch(PDO::FETCH_ASSOC);
       
        if (!$component) {
            throw new NotFound("Componente non esistente");
        }

        $path = FileHandler::getFilePath($request, $component["image_hash"]);

        $res = Response::new()
            ->ok()
            ->addFileInline($path);
        return $res;
    }
}