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

#[Route(Method::Delete, ["api", "manufacturers", "{manufacturerName}:{string}"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
class DeleteManufacturers extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        $name = $params->getString("manufacturerName");
        if(!isset($name)){
            throw new BadRequest("Inserisci un nome nel body");
        }

        $db = \DatabaseUtil\Database::getDatabase();
        

        $pr = $db->prepare("SELECT * FROM manufacturers WHERE url_name=?");
       
        $pr->execute([$name]);
        $exists = $pr->fetch(PDO::FETCH_ASSOC);

        if(!$exists){
            throw new BadRequest("Azienda non esistente");
        }

        $pr = $db->prepare("DELETE FROM manufacturers WHERE url_name=?");
       
        $success = $pr->execute([$name]);

        if($success){
            $res = Response::new()
                ->noContent();
        }else{
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    }   
}
