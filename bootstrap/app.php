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
            // Demo 抽獎頁（公開免登入，無真實敏感操作，session 無法持久化故豁免）
            'demo/lottery/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
