<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfDoctorAuthenticated
{
    /**
     * Guests on the doctor portal who are already signed in as a doctor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('doctor') !== null) {
            return redirect()->route('doctor.dashboard');
        }

        return $next($request);
    }
}
