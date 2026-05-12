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

#[Route(Method::Get, ["api", "download", "init.sh"], [], ContentTypes::DownloadFile)]
class Bash extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $res = Response::new()
        ->ok()
        ->addFile(Config::path("app.bash"), "init.sh");

        return $res; 
    } 
}
 