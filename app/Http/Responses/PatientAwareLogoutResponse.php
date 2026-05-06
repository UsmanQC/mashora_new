<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;
use Symfony\Component\HttpFoundation\Response;

class PatientAwareLogoutResponse implements LogoutResponse
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $referer = (string) $request->headers->get('referer', '');

        if (str_contains($referer, '/patient')) {
            return redirect()->route('patient.home');
        }

        return redirect()->route('home');
    }
}
