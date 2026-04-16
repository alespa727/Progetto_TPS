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
 
#[Route(Method::Get, ["swagger"], [], ContentTypes::Html)]
class Swagger extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $html = file_get_contents(Config::path("app.swagger"));
        $res = Response::new()
        ->ok()
        ->body($html);
        return $res;
    }
}
