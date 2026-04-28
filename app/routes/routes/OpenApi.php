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

use OpenApi\Attributes as OA;

#[OA\Info("1.0.0", "Documentazione progetto TPS di Alessio, Andrea, Ashan e Lorenzo", "Sketch PC API documentation")]
#[Route(Method::Get, ["api", "openapi"], [], ContentTypes::Json)]
class OpenApi extends Controller
{
    function manageRequest(Request $request, Params $params): Response
    {
        $openapi = (new \OpenApi\Generator())->generate([__DIR__ . '/../']);
        $openapi->servers = [
            new \OpenApi\Annotations\Server([
                'url' => 'http://' . $_SERVER['HTTP_HOST']
            ])
        ];
        $json = json_decode($openapi->toJson());

        $res = Response::new()
            ->ok()
            ->body($json);
        return $res;
    }
}
