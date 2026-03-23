<?php

class HelloUser extends Controller
{


    function manageRequest(Request $request, array $params): Response
    {
        if (empty($request->getQuery("name"))) {
            $name = "World";
        } else {
            $name = htmlspecialchars($request->getQuery("name"));
        }

        $html = "<div 
                    style='background-color: #000; 
                    display: flex; 
                    height: 100%; flex;  
                    justify-content: center; 
                    align-items: center;'
                >
                    <div style='font-weight: 600; font-size: 30px; color: #FFF; '>
                        Hello " . $name . "!
                    </div>
                </div>";
                
        $res = new Response();
        $res->ok();
        $res->body($html);
        $res->contentType(ContentTypes::Html);

        return $res;
    }



}