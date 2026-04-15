<?php

namespace Authorization;

use Core\ContentTypes;
use DatabaseUtil\Database;
use Core\Exceptions\Unauthorized;
use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use PDO;
use Core\Response;
use Core\Router;

final class Authorization
{
    public static string $secret = 'example_key_of_sufficient_length';

    public static function is_owner(): bool
    {
        /**
         * @var PDO $db
         */
        $db = Database::getDatabase();

        if (!array_key_exists("token", $_COOKIE))
            return false;

        $cookie_jwt = $_COOKIE["token"];
        $decoded = JWT::decode($cookie_jwt, new Key(self::$secret, 'HS256'), $headers);
        $decoded_array = (array) $decoded;

        $pr = $db->prepare("SELECT id, username, pfp_path, created_at, is_owner FROM users WHERE username=:username");

        $pr->execute(["username" => $decoded_array["username"]]);
        $user = $pr->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user["is_owner"])
                return true;
            else
                return false;
        } else
            return false;
    }
    public static function is_logged_in(): bool
    {
        /**
         * @var PDO $db
         */
        $db = Database::getDatabase();

        if (!array_key_exists("token", $_COOKIE))
            return false;

        $cookie_jwt = $_COOKIE["token"];
        $decoded = JWT::decode($cookie_jwt, new Key(self::$secret, 'HS256'), $headers);
        $decoded_array = (array) $decoded;

        $pr = $db->prepare("SELECT id, username, pfp_path, created_at, is_owner FROM users WHERE username=:username");

        $pr->execute(["username" => $decoded_array["username"]]);
        $user = $pr->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return true;
        } else
            return false;
    }

    public static function verify(): string
    {
        if (!Authorization::is_logged_in()) {
            $res = Response::new()
                ->unauthorized()
                ->body(["description" => "Esegui il login"]);

            Router::sendResponse($res, ContentTypes::Json);
        }

        $cookie_jwt = $_COOKIE["token"];
        $decoded = JWT::decode($cookie_jwt, new Key(self::$secret, 'HS256'), $headers);
        $decoded_array = (array) $decoded;
        return $decoded_array["username"];
    }
    public static function verifyOwnership(): void
    {
        if (!Authorization::is_logged_in()) {
            $res = Response::new()
                ->unauthorized()
                ->body(["description" => "Esegui il login"]);

            Router::sendResponse($res, ContentTypes::Json);
        } else if (!Authorization::is_owner()) {
            $res = Response::new()
                ->forbidden()
                ->body(["description" => "Non sei autorizzato"]);

            Router::sendResponse($res, ContentTypes::Json);
        }

    }
}
