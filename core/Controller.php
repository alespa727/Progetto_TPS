<?php

abstract class Controller
{
    public function __invoke(Request $request, array $params): Response{
        return $this->manageRequest($request, $params);
    }

    abstract function manageRequest(Request $request, array $params): Response;
}