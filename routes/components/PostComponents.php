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

#[Route(Method::Post, ["api", "components"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
class PostComponents extends Controller
{
   
    function validateRequest(Request $request, Params $params): bool {
        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");

        if(!isset($category) || !isset($manufacturer) || !isset($name)){
            return false;
        }

        return true;
    }


    function manageRequest(Request $request, Params $params): Response
    {
        $category = $request->getBody("category");
        $manufacturer = $request->getBody("manufacturer");
        $name = $request->getBody("name");
        $nome_url = str_replace(" ", "-", strtolower($name));
        $description = $request->getBody("description") ?? "";
        $quantity = $request->getBody("quantity") ?? 1;

        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("SELECT id FROM categories WHERE url_name=?");
        $pr->execute([$category]);

        $category_id = $pr->fetch(PDO::FETCH_ASSOC)["id"];

        $pr = $db->prepare("SELECT id FROM manufacturers WHERE url_name=?");
        $pr->execute([$manufacturer]);

        $manufacturer_id = $pr->fetch(PDO::FETCH_ASSOC)["id"];
        
        $pr = $db->prepare("INSERT INTO components (category_id, manufacturer_id, name, url_name, description, quantity) VALUES (?, ?, ?, ?, ?, ?)");
       
        $success = $pr->execute([$category_id, $manufacturer_id, $name, $nome_url, $description, $quantity]);
        
        if($success){
            $res = Response::new()
                ->created()
                ->body("");
        }else{
            throw new BadRequest("Componente non esistente");
        }
        return $res;

    } 
}
