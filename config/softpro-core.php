<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Standalone Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will register its own routes and use its own
    | layout if the host application doesn't provide one.
    |
    */
    'standalone' => env('SOFTPRO_CORE_STANDALONE', true),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix applied to all package routes when running in standalone mode.
    |
    */
    'route_prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Layout View
    |--------------------------------------------------------------------------
    |
    | The name of the Blade view that serves as the Inertia root template.
    | By default, it will try to use 'app' (host app) then fallback to
    | 'softpro-core::app' if standalone mode is on.
    |
    */
    'layout' => 'app',
];
