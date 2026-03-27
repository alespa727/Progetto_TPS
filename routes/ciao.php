<?php

class HelloUser extends Controller
{

    private function getHtml($text) : string {
        return  "<div 
                    style='background-color: #000; 
                    display: flex; 
                    height: 100%;  
                    justify-content: center; 
                    align-items: center;'
                >
                    <div style='font-weight: 600; font-size: 30px; color: #FFF; '>
                        ". $text . "
                    </div>
                </div>";
    }

    function validateRequest(Request $request, array $pathVariables): bool
    {
        if(strtolower($request->getQuery("name"))==="tommy"){
            return false;
        }
        return true;
    }

    function manageUnvalidRequest(Request $request, array $pathVariables): Response{
        $html = $this->getHtml("Vai in mona ".$request->getQuery("name"));

        return Response::new()
                ->ok()
                ->html($html);;
    }


    function manageRequest(Request $request, array $pathVariables): Response
    {
        if (empty($request->getQuery("name"))) {
            $name = "World";
        } else {
            $name = htmlspecialchars($request->getQuery("name"));
        }

        $html = $this->getHtml("Hello ".$name."!");
        return Response::new()
                ->ok()
                ->html($html);;
    }

}