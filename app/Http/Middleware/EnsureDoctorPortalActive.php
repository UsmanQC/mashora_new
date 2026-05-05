<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctorPortalActive
{
    /**
     * Reserved for parity with Mashorapwa-prod `doctor.is_active_status`
     * (session / availability). No-op for now.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
