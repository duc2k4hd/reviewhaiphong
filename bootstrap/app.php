<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        App\Providers\ViewServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdmin::class,
            'staff' => \App\Http\Middleware\CheckStaff::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Tạo sitemap hàng ngày lúc 2 giờ sáng
        $schedule->command('sitemap:generate')->daily()->at('02:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
