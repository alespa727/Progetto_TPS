<?php
namespace Core\Exceptions;

use Exception;

class NotFound extends Exception {
    public function __construct(string $msg = "Not Found") {
        parent::__construct($msg, 404);
    }
}
