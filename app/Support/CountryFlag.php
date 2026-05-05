<?php

namespace App\Support;

final class CountryFlag
{
    /**
     * Regional-indicator flag emoji from ISO 3166-1 alpha-2 (e.g. SA → 🇸🇦).
     */
    public static function emoji(string $isoAlpha2): string
    {
        $iso = strtoupper(trim($isoAlpha2));

        if (mb_strlen($iso) !== 2) {
            return '';
        }

        $a = mb_substr($iso, 0, 1);
        $b = mb_substr($iso, 1, 1);

        if (! ctype_upper($a) || ! ctype_upper($b)) {
            return '';
        }

        return mb_chr(ord($a) - 65 + 0x1F1E6).mb_chr(ord($b) - 65 + 0x1F1E6);
    }
}
