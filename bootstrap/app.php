<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\PreventBack;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'prevent-back' => PreventBack::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('customizations:cleanup')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
