<?php

use Core\Exceptions\BadRequest;
use Core\FileHandler;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Core\Config;

#[Route(Method::Get, ["api", "download", "queryfix", "{index}:{int}"], [], ContentTypes::DownloadFile)]
class QueryFix extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $res = Response::new()
        ->ok();

        if($params->getInt("index") === 1){
            $res = $res
            ->addFile(Config::path("app.queryfix1"), "GetComponents.php"); 
        }else if($params->getInt("index") === 2){
            $res = $res
            ->addFile(Config::path("app.queryfix2"), "GetAllComponents.php");
        }else{
            throw new BadRequest("Esiste solo fix 1 e 2");
        }

        return $res; 
    } 
}
 