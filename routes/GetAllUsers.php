<?php



#[Route(Method::Get, ["api", "users"], ContentTypes::Json)] 
class GetAllUsers extends Controller
{

    private $users = [
        "ale"=>[ 
            "id" => 0,
            "username" => "ale",
            "description" => "desc1"
        ],
        "tommy"=>[
            "id" => 1,
            "username" => "tommy",
            "description" => "tommy e' stato qui'"
        ],
        "ashan"=>[
            "id" => 2,
            "username" => "ashan",
            "description" => "bhiwehbfdsiyhdiybsdidhbashbiydasihbdasuyhb"
        ],
    ];
    function manageRequest(Request $request, array $pathVariables): Response
    {
        $res = new Response();
        $res->ok();
        $res->json($this->users);
      
        return $res;
    }

} 