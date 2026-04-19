<?php
namespace Core\Exceptions;

use Exception;

class Forbidden extends Exception {
    public function __construct(string $msg = "Forbidden") {
        parent::__construct($msg, 403);
    }
}
