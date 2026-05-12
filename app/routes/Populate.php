<?php

use Core\FileHandler;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Core\Config;

#[Route(Method::Get, ["api", "download", "populate.sql"], [], ContentTypes::DownloadFile)]
class Populate extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $res = Response::new()
        ->ok()
        ->addFile(Config::path("app.populate"), "populate.sql");

        return $res; 
    } 
}
 