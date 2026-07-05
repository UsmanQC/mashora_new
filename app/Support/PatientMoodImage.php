<?php

namespace App\Support;

final class PatientMoodImage
{
    /** @var list<string> */
    public const MOOD_KEYS = ['satisfied', 'neutral', 'disappointed', 'anxiety', 'happy', 'sad', 'tired', 'angry'];

    /** @var list<string> */
    private const EXTENSIONS = ['svg', 'png', 'webp'];

    /**
     * Public URL for a mood illustration in `public/images/patient-moods/{key}.{ext}`.
     */
    public static function url(?string $key): ?string
    {
        if ($key === null || $key === '' || ! in_array($key, self::MOOD_KEYS, true)) {
            return null;
        }

        foreach (self::EXTENSIONS as $ext) {
            $relative = 'images/patient-moods/'.$key.'.'.$ext;
            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        return null;
    }

    public static function emoji(string $key): string
    {
        return match ($key) {
            'satisfied' => '😌',
            'neutral' => '😐',
            'disappointed' => '😕',
            'anxiety' => '😰',
            'happy' => '😄',
            'sad' => '😔',
            'tired' => '🥱',
            'angry' => '😠',
            default => '•',
        };
    }
}
