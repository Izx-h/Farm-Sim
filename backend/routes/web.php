<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| AgriPulse is a single-page application rendered from one Blade view
| (`frontend/views/app.blade.php`) which mounts the Vue app. Every non-API
| path falls through to that view so the SPA can handle its own routing.
|
*/

Route::view('/', 'app');

Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|up).*$');
