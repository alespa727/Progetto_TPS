<?php


use Core\FileHandler;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;

#[Route(Method::Post, ["api", "protectedUpload"], ContentTypes::Json)] 
class AuthenticatedFileUploader extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $hash = FileHandler::addFile($_FILES["file"], [AuthMiddleware::class]);
        $res = Response::new()
        ->ok()
        ->body(["hash"=>$hash]);
        return $res;
    }
  
}