<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AllowGuestPatientPortalView
{
    /**
     * Allow guests to browse public patient portal pages (dashboard, filter,
     * specialists, important numbers) while still forcing authenticated users
     * with an incomplete profile to finish onboarding first.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->profile_completed) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        if (Str::startsWith($routeName, 'two-factor')) {
            return $next($request);
        }

        if ($request->routeIs(
            'patient.profile.basic',
            'patient.register.done',
            'logout',
        )) {
            return $next($request);
        }

        return redirect()->route('patient.profile.basic');
    }
}
