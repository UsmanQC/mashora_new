<?php

namespace App\Support;

final class ImportantNumbers
{
    /**
     * @return list<array{id: string, column: string, sort: int, label: string, phone: string, tel_href: string}>
     */
    public static function entries(): array
    {
        $locale = app()->getLocale();
        $labelKey = $locale === 'ar' ? 'label_ar' : 'label_en';

        /** @var list<array<string, mixed>> $raw */
        $raw = config('important_numbers.entries', []);

        $entries = [];
        foreach ($raw as $entry) {
            $phone = (string) ($entry['phone'] ?? '');
            $entries[] = [
                'id' => (string) ($entry['id'] ?? ''),
                'column' => (string) ($entry['column'] ?? 'left'),
                'sort' => (int) ($entry['sort'] ?? 0),
                'label' => (string) ($entry[$labelKey] ?? $entry['label_en'] ?? ''),
                'phone' => $phone,
                'tel_href' => self::telHref($phone),
            ];
        }

        usort($entries, static function (array $left, array $right): int {
            $columnOrder = ['left' => 0, 'right' => 1];

            if ($left['sort'] !== $right['sort']) {
                return $left['sort'] <=> $right['sort'];
            }

            return ($columnOrder[$left['column']] ?? 0) <=> ($columnOrder[$right['column']] ?? 0);
        });

        return $entries;
    }

    public static function telHref(string $phone): string
    {
        $digits = PatientPhone::normalize($phone);

        if ($digits === '') {
            return 'tel:';
        }

        if (strlen($digits) <= 6) {
            return 'tel:'.$digits;
        }

        if (str_starts_with($digits, '966')) {
            return 'tel:+'.$digits;
        }

        if (str_starts_with($digits, '0')) {
            return 'tel:+966'.ltrim($digits, '0');
        }

        return 'tel:+966'.$digits;
    }
}
