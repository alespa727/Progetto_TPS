<?php

use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;

#[Route(Method::Get, ["api", "hello"], [], ContentTypes::Html)]
#[Route(Method::Post, ["api", "hello"], [], ContentTypes::Html)]
class HelloUser extends Controller
{
    private function getHtml($text): string
    {
        return "<div 
                    style='background-color: #000; 
                    display: flex; 
                    height: 100%;   
                    justify-content: center; 
                    align-items: center;'
                >  
                    <div style='font-weight: 600; font-size: 30px; color: #FFF; '>
                        " . $text . " 
                    </div>
                </div>";
    }

    function validateRequest(Request $request, Params $params): bool
    {
        $name = $request->getQuery("name") ?? "";
        if (strtolower($name) === "tommy") {
            return false;
        }
        return true;
    }

    function manageUnvalidRequest(Request $request, Params $params): Response
    {
        $html = $this->getHtml("Vai in mona " . $request->getQuery("name"));

        return Response::new()
            ->ok()
            ->body($html);
    }

    function manageRequest(Request $request, Params $params): Response
    {
        if (empty($request->getQuery("name"))) {
            $name = "World";
        } else {
            $name = htmlspecialchars($request->getQuery("name"));
        }

        $html = $this->getHtml("Hello " . $name . "!");
        return Response::new()
            ->ok()
            ->body($html);
    }
}
