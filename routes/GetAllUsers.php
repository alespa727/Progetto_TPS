<?php
use Core\Route;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Method;
use Core\HttpResponseCodes;
use Core\ContentTypes;
use Core\Params;

#[Route(Method::Get, ["api", "users"], ContentTypes::Json)]
class GetAllUsers extends Controller
{
    private PDO $db;
 
    public function __construct()
    {
        $dsn = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=progetto_tps;charset=utf8mb4";
        $user = "db_user";
        $pass = "db_pass";

        $this->db = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    function manageRequest(Request $request, Params $params): Response
    {
        $res = new Response();
        try {
            $stmt = $this->db->query("SELECT id, username, password FROM users");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $res->ok();
            $res->body($users);
        } catch (PDOException $e) {
            $res->internalServerError();
            $res->body(["error" => $e->getMessage()]);
        }

        return $res;
    }
}