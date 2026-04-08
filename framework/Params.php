<?php
namespace Core;

class Params
{
    public function __construct(private array $params) {}

    public function getString(string $key): string {
        if (!isset($this->params[$key])) {
            throw new \Exception("Manca param $key", 400);
        }
        return (string)$this->params[$key];
    }

    public function getInt(string $key): int {
        if (!isset($this->params[$key])) {
            throw new \Exception("Manca param $key", 400);
        }
        return (int)$this->params[$key];
    }
}