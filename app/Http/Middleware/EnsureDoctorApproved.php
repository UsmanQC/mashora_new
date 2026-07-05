<?php

namespace App\Http\Middleware;

use App\Models\Doctor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctorApproved
{
    /**
     * Block doctors from the portal until a super admin approves their account.
     * Unapproved doctors are sent to the account-status screen; onboarding,
     * logout and locale switching remain accessible so they can finish setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $doctor = $request->user('doctor');

        if (! $doctor instanceof Doctor) {
            return $next($request);
        }

        if ($doctor->status === 'approved') {
            return $next($request);
        }

        if ($request->routeIs([
            'doctor.register.basic.info',
            'doctor.register.bank-account',
            'doctor.register.duration',
            'doctor.register.working-hours',
            'doctor.account-status',
            'doctor.logout',
            'doctor.locale',
        ])) {
            return $next($request);
        }

        return redirect()->route('doctor.account-status');
    }
}
