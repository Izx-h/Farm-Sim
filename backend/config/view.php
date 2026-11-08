<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views.
    |
    | NOTE: the Blade views live in the sibling `frontend/` folder
    | (`Farm Sim/frontend/views`), resolved relative to this backend app.
    |
    */

    'paths' => [
        base_path('../frontend/views'),
    ],

    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),

];
