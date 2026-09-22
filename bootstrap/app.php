<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', '')))), headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
        $middleware->web(append: [\App\Http\Middleware\EnsureAccountIsActive::class, \Illuminate\Session\Middleware\AuthenticateSession::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
