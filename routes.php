<?php

return array_merge(
    array_map( 
        fn($i) => Route::get(
                ["api", "resource$i"],  
                HelloUser::class
            )
            ->middleware(AuthMiddleware::class)
            ->contentType(ContentTypes::Json),
        range(1, 1000) 
        ), 
    [
        Route::get(["hello"], HelloUser::class)
            ->contentType(ContentTypes::Html),
        Route::get(["users", "{userId}"], GetUserById::class)
            ->middleware(AuthMiddleware::class),
        Route::get(["users", "{userId}", "idk"], GetUserById::class)
            ->middleware(AuthMiddleware::class),
        Route::get(["users"], GetAllUsers::class),
    ]   
      
);  