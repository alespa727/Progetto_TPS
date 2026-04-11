<?php
namespace Core\Exceptions;

use Exception;

class InternalServerError extends Exception {
    public function __construct(string $msg = "Internal Server Error") {
        parent::__construct($msg, 500);
    }
}
