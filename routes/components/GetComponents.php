<?php

use Core\Exceptions\BadRequest;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Core\ApiDoc;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

#[Route(Method::Get, ["api", "components", "{url_name}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
class GetComponents extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $url_name=$params->getString("url_name");
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("SELECT * FROM components WHERE url_name=?");
       
        $success = $pr->execute([$url_name]);
        $res = $pr->fetch(PDO::FETCH_ASSOC);
        if($success && $res){
            $res = Response::new()
                ->created()
                ->body($res);
        }else{
            throw new BadRequest("Componente non esistente");
        }
        return $res;

    } 
}
