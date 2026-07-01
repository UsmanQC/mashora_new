<?php

namespace App\Support;

final class PatientPageNavigation
{
    public static function usesLivewireNavigate(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || $path === '/') {
            return false;
        }

        return str_starts_with($path, '/patient');
    }
}
