<?php

use Core\Exceptions\NotFound;
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\ContentTypes;
use Core\Params;
use Core\Router;

#[Route(Method::Get, ["api", "routes"], [], ContentTypes::Json)]
class GetRoutes extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {

        /**
         * @var array<Route> $routes
         */
        $routes = Router::getRoutes();


        $routes_array_form = [];

        foreach ($routes as $key => $r) {
            $array = $r->toArray();
            $routes_array_form[] = $array;
        }
 
        $sorted_by_methods=[];
        $methods = Method::getMethodList();
        foreach ($methods as $key => $m) {
            foreach ($routes_array_form as $key => $route) {
                if($m===$route["method"]){
                    unset($route["pattern"]);
                    unset($route["controller_path"]);
                    unset($route["middlewares"]);
                    $sorted_by_methods[$m][] = $route;
                }
            }
        }
        
        $res = Response::new()
            ->ok()
            ->body($sorted_by_methods);

        return $res;
    }
}
 