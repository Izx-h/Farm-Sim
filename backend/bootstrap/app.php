<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

/*
|--------------------------------------------------------------------------
| Remap the database folder
|--------------------------------------------------------------------------
| This project is split into three sibling folders — frontend / backend /
| database. Point Laravel's `database_path()` at the sibling `database/`
| folder so migrations + the SQLite file resolve there. (The frontend
| Blade views are wired separately in `config/view.php`.)
*/
$app->useDatabasePath(dirname(__DIR__).'/../database');

return $app;
