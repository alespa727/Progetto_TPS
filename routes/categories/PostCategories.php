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

#[Route(Method::Post, ["api", "categories"], [OwnerAuthMiddleware::class], ContentTypes::Json)]
class PostCategories extends Controller
{
   
    function manageRequest(Request $request, Params $params): Response
    {
        
        $nome_categoria = $request->getBody("name");
        if(!isset($nome_categoria)){
            throw new BadRequest("Inserisci un nome nel body");
        }
        $nome_url = str_replace(" ", "-", strtolower($nome_categoria));
        /**
         * @var PDO $db
         */
        $db = \DatabaseUtil\Database::getDatabase();


        $pr = $db->prepare("INSERT INTO categories (name, url_name) values (?, ?)");

        $success = $pr->execute([$nome_categoria, $nome_url]);
        if($success){
            $res = Response::new()
                ->created()
                ->body(["description"=>"creata nuova categoria"]);
        }else{
            throw new BadRequest("Error Processing Request");
        }
        return $res;

    } 
}
