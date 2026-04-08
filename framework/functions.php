<?php
function getExplodedUri(): array
{
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $path = trim($uri, '/');

    return $path === '' ? [] : explode('/', $path);
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


function runMiddleware(\Core\Request $req, array $middleware, callable $final)
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
    $cacheDir = __DIR__ . '/cache';
    $hashFile = $cacheDir . '/routes.php.sha256';

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $currentHash = hash_file('sha256', $file);

    if (file_exists($hashFile)) {
        $oldHash = trim(file_get_contents($hashFile));

        if ($currentHash === $oldHash) {
            return false;
        }

        foreach (glob($cacheDir . '/*.php') as $cacheFile) {
            unlink($cacheFile);
        }
    }

    file_put_contents($hashFile, $currentHash);
    return true;
}

function routesHaveChanged(): bool {
    $hashFile = __DIR__ . '/cache/routes.sha256';
    $files = glob(__DIR__ . '/routes/*.php'); 
    $currentHash = '';

    foreach ($files as $file) {
        $currentHash .= filemtime($file);
    }
    $currentHash = hash('sha256', $currentHash);

    if (file_exists($hashFile) && trim(file_get_contents($hashFile)) === $currentHash) {
        return false;
    }

    file_put_contents($hashFile, $currentHash);
    return true; 
}

function var_export_short(array $array)
{
    return str_replace(['array (', ')'], ['[', ']'], var_export($array, true));
}