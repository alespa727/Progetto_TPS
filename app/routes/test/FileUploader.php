<?php

use Core\FileHandler;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
 
#[Route(Method::Post, ["api", "upload"], [], ContentTypes::Json)]
class FileUploader extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $hash = FileHandler::addFile($_FILES["file"], []);
        $res = Response::new()
        ->ok()
        ->body(["hash" => $hash]);
        return $res;
    }
}
