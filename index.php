<?php
error_reporting(E_ALL);

include_once "functions.php";
require "config.php";

$allowedHosts = [
    "localhost",
];

// Lista di routes
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
Router::handle($request, $routes, $allowedHosts);