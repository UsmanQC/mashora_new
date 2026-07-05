<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctorProfileCompleted
{
    /**
     * Match Mashorapwa-prod: dashboard routes require a completed onboarding profile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $doctor = $request->user('doctor');
        if ($doctor === null) {
            return $next($request);
        }

        if ($doctor->profile_completed) {
            return $next($request);
        }

        if ($request->routeIs([
            'doctor.register.basic.info',
            'doctor.register.bank-account',
            'doctor.register.duration',
            'doctor.register.working-hours',
            'doctor.logout',
        ])) {
            return $next($request);
        }

        return redirect()->route('doctor.register.basic.info');
    }
}
