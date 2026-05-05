<?php

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\Doctor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctorOwnsAppointment
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Appointment|null $appointment */
        $appointment = $request->route('appointment');

        if (! $appointment instanceof Appointment) {
            abort(404);
        }

        $doctor = auth('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        return $next($request);
    }
}
