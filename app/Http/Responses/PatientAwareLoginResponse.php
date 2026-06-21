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
        $user = $request->user();

        if ($user && ! $user->profile_completed) {
            return redirect()->route('patient.profile.basic');
        }

        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $this->isPatientPortalUrl($intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('patient.home');
    }

    private function isPatientPortalUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return false;
        }

        return $path === '/patient' || str_starts_with($path, '/patient/');
    }
}
