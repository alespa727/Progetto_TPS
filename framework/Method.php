<?php
namespace Core;
class Method
{
    const Post = "POST";
    const Get = "GET";
    const Put = "PUT";
    const Patch = "PATCH";
    const Delete = "DELETE";

    static function getMethodList(): array{
        return [Method::Get, Method::Post,Method::Delete, Method::Put, Method::Patch];
    }
}