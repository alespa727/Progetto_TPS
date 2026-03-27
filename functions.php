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

function cors(array $origins): bool
{

    if (!isset($_SERVER["HTTP_HOST"])) {
        return false;
    }

    $origin = $_SERVER["HTTP_HOST"];

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
        } else {
            $final($req);
        }
    };

    $next();
}


function didRouteFileChange()
{
    $file = 'routes.php';
    $hashFile = __DIR__ . '/cache/routes.php.sha256';
    if (!is_dir(__DIR__ . '/cache')) {
        mkdir(__DIR__ . '/cache', 0777, true);
    }

    $currentHash = hash_file('sha256', $file);

    if (file_exists($hashFile)) {
        $oldHash = trim(file_get_contents($hashFile));

        if ($currentHash === $oldHash) {
            return false;
        } else {
            file_put_contents($hashFile, $currentHash);
            return true;
        }
    } else {
        file_put_contents($hashFile, $currentHash);
        return true;
    }
}

function var_export_short(array $array)
{
    return str_replace(['array (', ')'], ['[', ']'], var_export($array, true));
}