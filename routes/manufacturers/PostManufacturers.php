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

#[Route(Method::Post, ["api", "manufacturers"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
class PostManufacturers extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        
        $name = $request->getBody("name");
        if(!isset($name)){
            throw new BadRequest("Inserisci un nome nel body");
        }
        $nome_url = str_replace(" ", "-", strtolower($name));
        /**
         * @var PDO $db
         */
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("INSERT INTO manufacturers (name, url_name) values (?, ?)");

        $success = $pr->execute([$name, $nome_url]);
        if($success){
            $res = Response::new()
                ->created()
                ->body(["description"=>"creata nuovo marca"]);
        }else{
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    } 
}
