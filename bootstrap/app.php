<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 🔧 CORS Middleware untuk API
       
        
        // ⚠️ HAPUS atau COMMENT baris ini jika masih error
        // $middleware->api('throttle:api');
        
        // 🔒 Atau gunakan rate limiter yang sudah didefinisikan
        // $middleware->api('throttle:60,1');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
