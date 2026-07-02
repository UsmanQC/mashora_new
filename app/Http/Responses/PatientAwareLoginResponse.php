<?php

namespace App\Http\Responses;

use App\Support\PendingPatientBooking;
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

        $intended = $request->session()->pull('url.intended');

        if (is_string($intended)) {
            PendingPatientBooking::captureFromUrl($intended);
        }

        if ($user && ! $user->profile_completed) {
            return redirect()->route('patient.profile.basic');
        }

        if ($bookingUrl = PendingPatientBooking::url()) {
            return redirect()->to($bookingUrl);
        }

        if (is_string($intended) && $this->isGuestAccessiblePatientUrl($intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('patient.home');
    }

    private function isGuestAccessiblePatientUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return false;
        }

        if ($path === '/patient') {
            return true;
        }

        $guestAccessiblePrefixes = [
            '/patient/filter',
            '/patient/specialists',
            '/patient/important-numbers',
        ];

        foreach ($guestAccessiblePrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
