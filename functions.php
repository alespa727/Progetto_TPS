<?php

function getExplodedUri()
{
    $uri = $_SERVER['REQUEST_URI'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = substr($uri, strlen($script));
    $path = ltrim($path, '/');
    $exploded = $path ? explode("/", $path) : [];
    return $exploded;
}

function cors(array $origins): bool {

    echo $_SERVER["HTTP_ORIGIN"];
    if (!isset($_SERVER["HTTP_ORIGIN"])) {
        return false;
    }

    $origin = $_SERVER["HTTP_ORIGIN"];

    foreach ($origins as $allowed) {
        if ($allowed === $origin) {
            header("Access-Control-Allow-Origin: $origin");
            return true;
        }
    }

    return false;
}


function runMiddleware(Request $req, array $middleware, callable $final)
{
    $index = 0;

    $next = function () use (&$index, $middleware, $req, &$next, $final) {
        if ($index < count($middleware)) {
            $current = new $middleware[$index]();
            $index++;
            $current($req, $next);
        }else{
            $final($req);
        }
    };

    $next();
}
