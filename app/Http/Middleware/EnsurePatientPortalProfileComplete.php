<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientPortalProfileComplete
{
    /**
     * Block patient portal areas until basic profile (gender, optional email) is completed.
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
