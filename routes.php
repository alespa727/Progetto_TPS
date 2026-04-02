<?php

/**
 * id types:
 * int
 * string
 * 
 * // GET /your/pattern
 * Route::get(["your", {yourId}:{yourType}, "pattern"], YourClass_That_Extends_Controller::class)
 * ->middleware(YourClass_That_Extends_Middleware::class)
 * ->contentType(ContentTypes::(YourChoice))
 */
return 
    [
        Route::get(["hello"], HelloUser::class)
            ->contentType(ContentTypes::Html),
        Route::get(["api", "users", "{userId}:{string}"], GetUserById::class)
            ->middleware(AuthMiddleware::class),
        Route::get(["api", "users", "Hello"], HelloUser::class)
            ->contentType(ContentTypes::Html)
            ->middleware(AuthMiddleware::class),
        Route::get(["api", "users"], GetAllUsers::class),
    ]; 
                