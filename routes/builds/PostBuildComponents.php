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

#[Route(Method::Post, ["api", "builds", "{buildId}:{int}", "components"], [], ContentTypes::Json)]
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

    if (!$build)
        throw new BadRequest("Build non disponibile");

    $stmt = $db->prepare("SELECT * FROM components WHERE id = ?");
    $stmt->execute([$component_id]);
    $component = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$component)
        throw new BadRequest("Componente non disponibile");

    $stmt = $db->prepare("SELECT * FROM component_specs WHERE component_id = ?");
    $stmt->execute([$component_id]);
    $specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("
        SELECT C.id, C.name, C.category_id,
               CS.spec_key, CS.spec_value
        FROM build_components BC
        INNER JOIN components C ON C.id = BC.component_id
        INNER JOIN component_specs CS ON CS.component_id = C.id
        WHERE BC.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


    return Response::new()->ok()->body(["message" => $specs ]);
}
}
