<?php

namespace App\Support;

final class CountryPhoneTerritories
{
    /**
     * @return list<array{iso: string, dial: string}>
     */
    public static function all(): array
    {
        /** @var list<array{iso: string, dial: string}> */
        return config('country_phone_territories');
    }

    /**
     * Dial digits (no +) for a territory ISO code, or null if unknown.
     */
    public static function dialForIso(string $isoAlpha2): ?string
    {
        $iso = strtoupper(trim($isoAlpha2));
        $row = collect(self::all())->firstWhere('iso', $iso);

        return $row['dial'] ?? null;
    }

    /**
     * Sorted rows for dropdown: flag, ISO, dial, localized label.
     *
     * @return list<array{iso: string, dial: string, flag: string, label: string}>
     */
    public static function sortedRowsForLocale(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $rows = self::all();
        $decorated = [];

        foreach ($rows as $row) {
            $iso = $row['iso'];
            $decorated[] = [
                'iso' => $iso,
                'dial' => $row['dial'],
                'flag' => CountryFlag::emoji($iso),
                'label' => self::territoryLabel($iso, $locale),
            ];
        }

        usort($decorated, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return $decorated;
    }

    private static function territoryLabel(string $iso, string $locale): string
    {
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('und-'.$iso, $locale);
            if ($name !== '' && $name !== false) {
                return $name;
            }
        }

        return $iso;
    }
}
