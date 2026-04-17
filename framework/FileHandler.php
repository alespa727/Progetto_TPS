<?php
namespace Core;
use Symfony\Component\VarExporter\VarExporter;

class FileHandler
{

    private static $staticPath = __DIR__ . "/../static/";


    public static function getStaticFilesPath(): string
    {
        return FileHandler::$staticPath;
    }

    public static function setStaticFilesPath($path): void
    {
        FileHandler::$staticPath = $path;
    }

    public static function addFile(array $file, array $middlewares): string
    {
        if (!is_dir(__DIR__ . '/file_permissions/')) {
            mkdir(__DIR__ . '/file_permissions/', 0775, true);
        }

        $tmp = $file['tmp_name'];
        $name = $file['name'];

        $hash = hash_file('sha256', $tmp);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $hashedName = $hash . ($ext ? "." . $ext : "");

        $destination = FileHandler::getStaticFilesPath() . "/" . $hashedName;
        $file["middlewares"] = $middlewares;
        $file["hash"] = $hash;
        $file["ext"] = $ext;
        move_uploaded_file($tmp, $destination);


        $permissions_file = __DIR__ . '/file_permissions/' . $hash . '.php';

        $data = "<?php\nreturn " . VarExporter::export($file) . ";\n";

        file_put_contents($permissions_file, $data);

        return $hash;
    }

    public static function getFilePath(Request $r, string $hash): string
    {
        $hash = basename($hash);
        if (!is_dir(__DIR__ . '/file_permissions/')) {
            mkdir(__DIR__ . '/file_permissions/', 0775, true);
        }

        $permissions = null;
        if (file_exists(__DIR__ . '/file_permissions/' . $hash . '.php')) {
            $permissions = (require __DIR__ . '/file_permissions/' . $hash . '.php');
            $middlewares = $permissions["middlewares"];
            foreach ($middlewares as $key => $middleware) {
                require Config::path("directories.middlewares")."/".$middleware.".php";
            }
        } else
            return null;

        $path = null;
        runMiddleware($r, $middlewares, function () use ($permissions, &$path) {

            if (file_exists(FileHandler::getStaticFilesPath() . "/" . $permissions["hash"] .".". $permissions["ext"])) {
                $path = FileHandler::getStaticFilesPath() . "/" . $permissions["hash"] .".". $permissions["ext"];
            }

        });

        return $path;
    }

     public static function getFileName(Request $r, string $hash): string
    {
        $hash = basename($hash);
        if (!is_dir(__DIR__ . '/file_permissions/')) {
            mkdir(__DIR__ . '/file_permissions/', 0775, true);
        }

        $permissions = null;
        if (file_exists(__DIR__ . '/file_permissions/' . $hash . '.php')) {
            $permissions = (require __DIR__ . '/file_permissions/' . $hash . '.php');
            $middlewares = $permissions["middlewares"];
            foreach ($middlewares as $key => $middleware) {
                require Config::path("directories.middlewares")."/".$middleware.".php";
            }
        } else
            return null;

        $name = null;
        runMiddleware($r, $middlewares, function () use ($permissions, &$name) {

            if (file_exists(FileHandler::getStaticFilesPath(). "/" . $permissions["hash"] .".". $permissions["ext"])) {
                $name =$permissions["name"];
            }

        });

        return $name;
    }

    public static function sendFileDownloadResponse(string $path, string $filename)
    {
        if (!file_exists($path)) {
            http_response_code(404);
            echo "File not found";
            return;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');

        readfile($path);
        exit;
    }

    public static function returnInlineFile(string $path)
    {
        if (!file_exists($path)) {
            http_response_code(404);
            return;
        }

        $mime = mime_content_type($path);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));

        readfile($path);
        exit;
    }
}