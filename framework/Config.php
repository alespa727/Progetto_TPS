<?php

namespace Core;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private static array $config = [];

    public static function load(string $file): void
    {
        self::$config = Yaml::parseFile($file);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $keys = explode(".", $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                if(self::get("app.debug") && self::get("app.debug")!==null){
                    $res = Response::new()
                            ->internalServerError()
                            ->body("Configurare il config.yaml correttamente, manca: ".$key);
                    Router::sendResponse($res, ContentTypes::Json);
                }
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public static function path(string $key): string
    {
        $relative = self::get($key);
        return BASE_PATH . DIRECTORY_SEPARATOR . $relative;
    }
}