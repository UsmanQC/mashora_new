<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateBroadcastParticipant
{
    /**
     * Use the doctor or patient web guard for Pusher private channel authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('doctor')) {
            auth()->shouldUse('doctor');

            return $next($request);
        }

        if ($request->user('web')) {
            auth()->shouldUse('web');

            return $next($request);
        }

        throw new AccessDeniedHttpException;
    }
}
