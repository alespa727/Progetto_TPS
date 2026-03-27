<?php 
    
    $paths = [
        "core",
        "middlewares",
        "routes"
    ];

    foreach ($paths as $key => $path) {
        foreach (glob($path . "/*.php") as $filename) {
            include_once $filename;
        }
    }