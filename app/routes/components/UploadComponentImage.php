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

#[Route(Method::Post, ["api", "components", "{url_name}:{string}", "image"], [OwnerAuthMiddleware::class], ContentTypes::Json)]

#[OA\Post(
    path: "/api/components/{url_name}/image",
    summary: "Upload immagine componente",
    description: "Carica o aggiorna l'immagine associata a un componente identificato da url_name",
    tags: ["Component-Images"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["file"],
                properties: [
                    new OA\Property(
                        property: "file",
                        type: "string",
                        format: "binary",
                        description: "File immagine da caricare"
                    )
                ]
            )
        )
    ),
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
            description: "Immagine aggiornata con successo",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Componente aggiornato"
                    ),
                    new OA\Property(
                        property: "hash",
                        type: "string",
                        example: "a8f3c91d2b..."
                    )
                ]
            )
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
class UploadComponentImage extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $url_name = $params->getString("url_name");

        $db = \DatabaseUtil\Database::getDatabase();

        $stmt = $db->prepare("SELECT id, category_id FROM components WHERE url_name=?");
        $stmt->execute([$url_name]);
        $component = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$component) {
            throw new NotFound("Componente non esistente");
        }
       
        $hash = FileHandler::addFile($_FILES["file"], []);

        $sql = "UPDATE components SET image_hash=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hash, $component["id"]]);


        $res = Response::new()
        ->ok()
        ->body(["hash" => $hash]);
        return $res;
    }
}