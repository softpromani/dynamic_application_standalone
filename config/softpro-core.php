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

    /*
    |--------------------------------------------------------------------------
    | Auth Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically register the 'applicant'
    | guard and provider in the host application.
    |
    */
    'register_guards' => true,

    /*
    |--------------------------------------------------------------------------
    | Consistent Routing
    |--------------------------------------------------------------------------
    |
    | Default routes for package redirects.
    |
    */
    'home_route' => 'admin.dashboard',
    'login_route' => 'admin.login',

    /*
    |--------------------------------------------------------------------------
    | Root Route Control
    |--------------------------------------------------------------------------
    |
    | When set to false, the package will not register the '/' route,
    | allowing the host application to define its own home page.
    |
    */
    'enable_root_route' => true,
];
