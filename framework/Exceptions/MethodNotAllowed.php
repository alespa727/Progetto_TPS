<?php
namespace Core\Exceptions;

use Exception;

class MethodNotAllowed extends Exception {
    public function __construct(string $msg = "Method Not Allowed") {
        parent::__construct($msg, 405);
    }
}
