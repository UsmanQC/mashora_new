<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;
use Symfony\Component\HttpFoundation\Response;

class PatientAwareLoginResponse implements LoginResponse
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        /** @var Request $request */
        if ($request->boolean('patient_flow')) {
            $user = $request->user();

            if ($user && ! $user->profile_completed) {
                return redirect()->route('patient.profile.basic');
            }

            return redirect()->intended(route('patient.home'));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
