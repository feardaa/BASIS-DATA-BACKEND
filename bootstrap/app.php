<?php
// Force file cache driver
if (!isset($_ENV['CACHE_DRIVER'])) {
    $_ENV['CACHE_DRIVER'] = 'file';
    $_ENV['SESSION_DRIVER'] = 'file';
    putenv('CACHE_DRIVER=file');
    putenv('SESSION_DRIVER=file');
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Konfigurasi penanganan exception di sini
    })->create();