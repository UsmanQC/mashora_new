<?php

namespace App\Support;

final class ImportantNumbers
{
    /** @var list<string> */
    private const CATEGORY_ORDER = ['national', 'regional'];

    /**
     * @return list<array{id: string, category: string, sort: int, label: string, phone: string, tel_href: string}>
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
                'category' => (string) ($entry['category'] ?? 'regional'),
                'sort' => (int) ($entry['sort'] ?? 0),
                'label' => (string) ($entry[$labelKey] ?? $entry['label_en'] ?? ''),
                'phone' => $phone,
                'tel_href' => self::telHref($phone),
            ];
        }

        usort($entries, static function (array $left, array $right): int {
            $leftCategory = array_search($left['category'], self::CATEGORY_ORDER, true);
            $rightCategory = array_search($right['category'], self::CATEGORY_ORDER, true);

            if ($leftCategory !== $rightCategory) {
                return ($leftCategory !== false ? $leftCategory : 99) <=> ($rightCategory !== false ? $rightCategory : 99);
            }

            return $left['sort'] <=> $right['sort'];
        });

        return $entries;
    }

    /**
     * @return array{national: list<array{id: string, category: string, sort: int, label: string, phone: string, tel_href: string}>, regional: list<array{id: string, category: string, sort: int, label: string, phone: string, tel_href: string}>}
     */
    public static function groupedEntries(): array
    {
        $grouped = [
            'national' => [],
            'regional' => [],
        ];

        foreach (self::entries() as $entry) {
            $category = $entry['category'] === 'national' ? 'national' : 'regional';
            $grouped[$category][] = $entry;
        }

        return $grouped;
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
