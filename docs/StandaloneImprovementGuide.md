# Standalone Improvement Guide

## Overview
The current standalone installation of **Softpro Core** works, but a redirect loop occurs when an applicant tries to access the profile‑setup page. The loop is caused by the `EnsureProfileIsComplete` middleware attempting to redirect even when no profile template exists, and by missing guard configuration.

## What Triggers the Loop
- **Missing Profile Template** – The middleware checks `is_profile_complete` and redirects to `applicant.profile-setup` regardless of whether a profile template is available.
- **Guard Misconfiguration** – The applicant guard is defined, but the middleware is attached to routes that also use the `auth:applicant` middleware, causing repeated redirects.
- **Cache Stale** – After changes, cached routes or config can keep the old middleware behavior.

## Fixes for Developers
### 1. Update `EnsureProfileIsComplete` Middleware
Replace the current logic with a guard that only redirects when:
1. The user exists and the profile is **not** complete.
2. An **active** profile template exists.

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Softpro\Core\Models\FormTemplate;

class EnsureProfileIsComplete
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('applicant')->user();
        // Only act on logged‑in applicants
        if ($user && ! $user->is_profile_complete) {
            $hasTemplate = FormTemplate::where('is_profile', true)
                ->where('is_active', true)
                ->exists();
            if ($hasTemplate && ! $request->routeIs('applicant.profile-setup', 'applicant.profile-update', 'applicant.logout')) {
                return redirect()->route('applicant.profile-setup');
            }
        }
        return $next($request);
    }
}
```

### 2. Register the Middleware Correctly
In `vendor/softpro/core/routes/tenant.php` ensure the middleware array includes the updated class:
```php
Route::middleware([
    'web',
    \Softpro\Core\Http\Middleware\SetStandaloneRootView::class,
    'auth:applicant',
    \App\Http\Middleware\EnsureProfileIsComplete::class,
])->group(function () {
    // … routes …
});
```

### 3. Verify Guard Configuration
Open `config/auth.php` and confirm:
```php
'guards' => [
    'applicant' => [
        'driver'   => 'session',
        'provider' => 'applicants',
    ],
],
'providers' => [
    'applicants' => [
        'driver' => 'eloquent',
        'model'  => \Softpro\Core\Models\Applicant::class,
    ],
],
```

### 4. Clear Caches & Re‑compile Assets
```bash
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:clear
npm run dev   # or npm run build for production
```

### 5. Optional: Add a Debug Log
```php
use Illuminate\Support\Facades\Log;
Log::info('EnsureProfileIsComplete – user:', [$user?->id, $user?->is_profile_complete]);
```

## Testing the Fix
1. Register a new applicant.
2. Log in – you should be redirected to the profile‑setup page once.
3. Complete the profile and submit.
4. Verify you are taken to the dashboard without another redirect.

## Deployment Checklist
- Update `EnsureProfileIsComplete.php`.
- Commit the change and run `composer dump‑autoload`.
- Clear all Laravel caches.
- Re‑build front‑end assets.
- Verify routes via `php artisan route:list`.
