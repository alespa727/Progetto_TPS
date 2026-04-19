<?php
namespace Core\Exceptions;

use Exception;

class BadRequest extends Exception {
    public function __construct(string $msg = "Bad Request") {
        parent::__construct($msg, 400);
    }
}
