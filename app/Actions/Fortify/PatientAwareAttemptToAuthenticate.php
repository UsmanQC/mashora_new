<?php

namespace App\Actions\Fortify;

use App\Support\PatientPhone;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Fortify;

class PatientAwareAttemptToAuthenticate extends AttemptToAuthenticate
{
    /**
     * @param  Request  $request
     *
     * @throws ValidationException
     */
    protected function throwFailedAuthenticationException($request): void
    {
        $this->limiter->increment($request);

        $exception = ValidationException::withMessages([
            Fortify::username() => [trans('auth.failed')],
        ]);

        if ($request->boolean('patient_flow')) {
            $phone = PatientPhone::normalize((string) $request->input(Fortify::username()));

            if ($phone !== '') {
                $exception->redirectTo(route('patient.phone', ['phone' => $phone]));
            }
        }

        throw $exception;
    }
}
