<?php

use Authorization\Authorization;
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

#[Route(Method::Post, ["api", "profile", "avatar"], [AuthMiddleware::class], ContentTypes::Json)]

#[OA\Post(
    path: "/api/profile/avatar",
    summary: "Upload avatar profilo",
    description: "Carica o aggiorna l'immagine associata al tuo profile",
    tags: ["Profile-Images"],
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
    responses: [
        new OA\Response(
            response: 200,
            description: "Avatar aggiornato con successo",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Avatar aggiornato"
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
            response: 400,
            description: "File non valido o mancante"
        )
    ]
)]
class UploadProfileImage extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $db = \DatabaseUtil\Database::getDatabase();

        $userId = Authorization::userId();
       
        $hash = FileHandler::addFile($_FILES["file"], []);

        $sql = "UPDATE users SET pfp_hash=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hash, $userId]);


        $res = Response::new()
        ->ok()
        ->body(["hash" => $hash]);
        return $res;
    }
}