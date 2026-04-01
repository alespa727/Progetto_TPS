<?php

use Doctrine\DBAL\DriverManager;

class GetUserById extends Controller
{
    private $users = [
        "ale" => [
            "id" => 0,
            "username" => "ale",
            "description" => "desc1"
        ],
        "tommy" => [
            "id" => 1,
            "username" => "tommy",
            "description" => "tommy e' stato qui'"
        ],
        "ashan" => [
            "id" => 2,
            "username" => "ashan",
            "description" => "bhiwehbfdsiyhdiybsdidhbashbiydasihbdasuyhb"
        ],
    ];

    function manageRequest(Request $request, array $pathVariables): Response
    {

        try {
            $userId = $pathVariables["userId"];
            $user = $this->users[$userId];

            $res = new Response();
            $res->ok();
            $res->json($user);
        } catch (\Throwable $th) {
            throw new Exception("Inserire un id valido", HttpResponseCode::BAD_REQUEST);
        }


        return $res;
    }

}
