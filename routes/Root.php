<?php

use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use OpenApi\Attributes as OA;

#[Route(Method::Get, [], [], ContentTypes::Html)]
#[OA\Get(
    path: "/",
    summary: "Welcomes the user into my web service",
    parameters: [
        new OA\Parameter(
            name: "name",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "string")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "OK")
    ]
)]
class Root extends Controller
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

    function manageRequest(Request $request, Params $params): Response
    {
        if (empty($request->getQuery("name"))) {
            $name = "World";
        } else {
            $name = htmlspecialchars($request->getQuery("name"));
        }

        $html = $this->getHtml("Welcome to my web service " . $name . "!");
        return Response::new()
            ->ok()
            ->body($html);
    }
}
