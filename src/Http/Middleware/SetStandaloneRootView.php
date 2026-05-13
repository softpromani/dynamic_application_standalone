<?php

namespace Softpro\Core\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Closure;

class SetStandaloneRootView
{
    public function handle(Request $request, Closure $next)
    {
        if (config('softpro-core.standalone')) {
            $layout = config('softpro-core.layout');
            
            // If the host app doesn't have the layout, fallback to package layout
            if (!view()->exists($layout)) {
                Inertia::setRootView('softpro-core::app');
            } else {
                Inertia::setRootView($layout);
            }
        }

        return $next($request);
    }
}
