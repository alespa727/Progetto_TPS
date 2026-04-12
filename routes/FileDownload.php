<?php

use Core\FileHandler;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;

#[Route(Method::Get, ["api", "download", "{fileName}:{string}"], ContentTypes::DownloadFile)] 
class FileDownload extends Controller
{

    function manageRequest(Request $request, Params $params): Response
    {
        $path = FileHandler::getFilePath($request, $params->getString("fileName"));
        $name = FileHandler::getFileName($request, $params->getString("fileName"));

        $res = Response::new()
        ->ok()
        ->addFile($path, $name);

        return $res;
    }
 
}