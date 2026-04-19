<?php
namespace Core\Exceptions;

use Exception;

class Unauthorized extends Exception {
    public function __construct(string $msg = "Unauthorized") {
        parent::__construct($msg, 401);
    }
}
