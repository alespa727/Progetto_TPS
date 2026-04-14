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

#[Route(Method::Delete, ["api", "categories", "{categoryName}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
class DeleteCategories extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $name = $params->getString("categoryName");
       
        if(!isset($name)){
            throw new BadRequest("Inserisci un nome valido");
        }

        $db = \DatabaseUtil\Database::getDatabase();
        
         $pr = $db->prepare("SELECT * FROM categories WHERE url_name=?");
       
        $pr->execute([$name]);
        $exists = $pr->fetch(PDO::FETCH_ASSOC);

        if(!$exists){
            throw new BadRequest("Nome non valido");
        }


        $pr = $db->prepare("DELETE FROM categories WHERE name=?");
       
        $success = $pr->execute([$name]);
        if($success){
            $res = Response::new()
                ->noContent();
        }else{
            throw new BadRequest("Nome non valido");
        }
        return $res;

    }   
}
