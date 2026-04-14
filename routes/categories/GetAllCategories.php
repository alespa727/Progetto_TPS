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

#[Route(Method::Get, ["api", "categories"], [AuthMiddleware::class], ContentTypes::Json)]
class GetAllCategories extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("SELECT * FROM categories");
        
        $success = $pr->execute();
        $res = $pr->fetchAll(PDO::FETCH_ASSOC);
        if($success){
            $res = Response::new()
                ->ok()
                ->body($res);
        }else{
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    } 
}
