<?php

namespace routes;

use Core\Config;
use Core\ContentTypes;
use Core\Controller;
use Core\Method;
use Core\Params;
use Core\Request;
use Core\Response;
use Core\Route;

#[Route(Method::Get, ["docs"], [], ContentTypes::Html)]
class Docs extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $html = file_get_contents(Config::path("app.docs"));
        $res = Response::new()
            ->ok()
            ->body($html);
        return $res;
    }
}
