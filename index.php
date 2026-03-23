<?php
error_reporting(E_ALL);

include_once "functions.php";

$allowedHosts = [
    "http://localhost",
];

if (!cors($allowedHosts)) {
    $res = new Response();
    $res->badRequest();
    $res->body(["error" => "Richiesta da hostname non valido"]);
    $res->contentType(ContentTypes::Json);
    Router::sendResponse($res);
}


$paths = [
    "core",
    "middlewares",
    "routes"
];

foreach ($paths as $key => $path) {
    foreach (glob($path . "/*.php") as $filename) {
        include_once $filename;
    }
}

$routes = [
    Route::get(["hello"], HelloUser::class)
        ->contentType(ContentTypes::Html),
    Route::get(["users", "{userId}"], GetUserById::class)
        ->middleware(AuthMiddleware::class),
    Route::get(["users"], GetAllUsers::class),
];

// Inizializzo la richiesta
$request = new Request();

// Gestione della richiesta automatica
Router::match($request, $routes, $allowedHosts);