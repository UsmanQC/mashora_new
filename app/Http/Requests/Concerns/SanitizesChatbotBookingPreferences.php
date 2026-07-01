<?php

namespace App\Http\Requests\Concerns;

trait SanitizesChatbotBookingPreferences
{
    protected function prepareForValidation(): void
    {
        if (! is_array($preferences = $this->input('preferences'))) {
            return;
        }

        $clean = [];

        foreach ($preferences as $key => $value) {
            if ($key === 'subspecialties') {
                $clean[$key] = is_array($value)
                    ? array_values(array_filter($value, static fn (mixed $item): bool => filled($item)))
                    : [];

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $clean[$key] = $value;
        }

        $this->merge(['preferences' => $clean]);
    }
}
