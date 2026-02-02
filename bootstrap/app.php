<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 排除綠界回調路由的 CSRF 驗證
        $middleware->validateCsrfTokens(except: [
            'payment/ecpay/notify',
            'payment/ecpay/result',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
