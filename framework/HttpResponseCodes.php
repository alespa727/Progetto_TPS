<?php
namespace Core;

class HttpResponseCodes {

    // Success
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;

    // Client Errors
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
      const METHOD_NOT_ALLOWED = 405;

    // Server Errors
    const INTERNAL_SERVER_ERROR = 500;
}