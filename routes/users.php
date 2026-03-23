<?php

class GetUserById extends Controller
{

    private $users = [
        [
            "id" => 0,
            "username" => "ale",
            "description" => "desc1"
        ],
        [
            "id" => 1,
            "username" => "tommy",
            "description" => "tommy e' stato qui'"
        ],
        [
            "id" => 2,
            "username" => "ashan",
            "description" => "bhiwehbfdsiyhdiybsdidhbashbiydasihbdasuyhb"
        ],
    ];

    function manageRequest(Request $request, array $params): Response
    {
         $userId = $params["userId"];

        if (empty($userId)) {
            $res = new Response();
            $res->badRequest();
            $res->body([
                "message" => "Manca l'id"
            ]);
            $res->contentType(ContentTypes::Json);

            return $res;
        }

        $user = $this->users[$userId];
        http_response_code(200);

        $res = new Response();
        $res->ok();
        $res->body($user);
        $res->contentType(ContentTypes::Json);

        return $res;
    }

}


class GetAllUsers extends Controller
{

    private $users = [
        [
            "id" => 0,
            "username" => "ale",
            "description" => "desc1"
        ],
        [
            "id" => 1,
            "username" => "tommy",
            "description" => "tommy e' stato qui'"
        ],
        [
            "id" => 2,
            "username" => "ashan",
            "description" => "bhiwehbfdsiyhdiybsdidhbashbiydasihbdasuyhb"
        ],
    ];
    function manageRequest(Request $request, array $params): Response
    {
        $res = new Response();
        $res->ok();
        $res->body($this->users);
        $res->contentType(ContentTypes::Json);
        
        return $res;
    }

}