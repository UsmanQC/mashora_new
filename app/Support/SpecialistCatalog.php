<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\Speciality;

final class SpecialistCatalog
{
    /**
     * @return list<array<string, mixed>> Card-shaped rows for patient-specialist-result-card
     */
    public static function all(): array
    {
        /** @var array<int, array<string, mixed>> $entries */
        $entries = config('specialist_catalog.entries', []);

        $cards = [];
        foreach ($entries as $entry) {
            $cards[] = self::toCardShape($entry);
        }

        return $cards;
    }

    /**
     * Apply session filter preferences from the schedule session screen.
     *
     * @param  array<string, mixed>|null  $preferences
     * @return list<array<string, mixed>>
     */
    public static function filtered(?array $preferences): array
    {
        $cards = self::all();

        if ($preferences === null || $preferences === []) {
            return $cards;
        }

        return array_values(array_filter(
            $cards,
            static fn (array $doc): bool => self::matchesFilters($doc, $preferences)
        ));
    }

    /**
     * @param  array<string, mixed>  $doc  Normalised card (includes filter metadata)
     * @param  array<string, mixed>  $preferences
     */
    private static function matchesFilters(array $doc, array $preferences): bool
    {
        $roleWant = $preferences['specialist_role'] ?? null;
        if (is_string($roleWant) && $roleWant !== '' && ($doc['specialist_role'] ?? '') !== $roleWant) {
            return false;
        }

        $genderPref = $preferences['gender_preference'] ?? 'both';
        if (in_array($genderPref, ['male', 'female'], true) && ($doc['gender'] ?? '') !== $genderPref) {
            return false;
        }

        $durationWant = isset($preferences['duration_minutes']) ? (string) $preferences['duration_minutes'] : '';
        if ($durationWant !== '' && (string) ($doc['session_minutes'] ?? '') !== $durationWant) {
            return false;
        }

        $langPref = $preferences['language_preference'] ?? 'both';
        /** @var list<string> $docLangs */
        $docLangs = is_array($doc['languages'] ?? null) ? $doc['languages'] : [];
        if ($langPref === 'ar' && ! in_array('ar', $docLangs, true)) {
            return false;
        }
        if ($langPref === 'en' && ! in_array('en', $docLangs, true)) {
            return false;
        }

        $selectedSubs = $preferences['subspecialties'] ?? [];
        if (is_array($selectedSubs) && count($selectedSubs) > 0) {
            /** @var list<string> $docIds */
            $docIds = is_array($doc['speciality_ids'] ?? null) ? $doc['speciality_ids'] : [];
            $intersection = array_intersect(
                array_map(static fn (mixed $k): string => (string) $k, $selectedSubs),
                $docIds
            );
            if (count($intersection) === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $entry  Raw config row
     * @return array<string, mixed>
     */
    private static function toCardShape(array $entry): array
    {
        $locale = app()->getLocale();
        $isAr = $locale === 'ar';

        $name = $isAr ? ($entry['name_ar'] ?? $entry['name'] ?? '') : ($entry['name'] ?? '');
        $bio = $isAr ? ($entry['bio_ar'] ?? $entry['bio'] ?? '') : ($entry['bio'] ?? '');

        /** @var list<int> $specialityIds */
        $specialityIds = is_array($entry['speciality_ids'] ?? null)
            ? array_values(array_map(static fn (mixed $id): int => (int) $id, $entry['speciality_ids']))
            : [];

        $tags = [];
        if ($specialityIds !== []) {
            $tags = Speciality::query()
                ->whereIn('id', $specialityIds)
                ->orderBy('id')
                ->get()
                ->map(function (Speciality $s) use ($isAr): string {
                    return $isAr
                        ? (filled($s->title_ar) ? (string) $s->title_ar : (string) $s->title)
                        : (filled($s->title) ? (string) $s->title : (string) $s->title_ar);
                })
                ->all();
        }

        /** @var list<string> $specialityIdStrings */
        $specialityIdStrings = array_map(static fn (int $id): string => (string) $id, $specialityIds);

        $specialistRole = (string) ($entry['specialist_role'] ?? '');
        $roleKind = match ($specialistRole) {
            'psychologist_non_md' => 'therapist',
            default => 'physician_specialist',
        };

        $explicitDoctorId = self::normaliseOptionalDoctorId($entry['doctor_database_id'] ?? null);
        $fallbackDoctorId = self::normaliseOptionalDoctorId(config('patient_booking.catalog_doctor_fallback_id'));

        return [
            'id' => (string) ($entry['id'] ?? ''),
            'name' => $name,
            'bio' => $bio,
            'role_kind' => $roleKind,
            'likes' => (int) ($entry['likes'] ?? 0),
            'price_sar' => (int) ($entry['price_sar'] ?? 0),
            'session_minutes' => (int) ($entry['session_minutes'] ?? 15),
            'channels' => is_array($entry['channels'] ?? null) ? $entry['channels'] : [],
            'slots' => is_array($entry['slots'] ?? null) ? $entry['slots'] : [],
            'tags' => $tags,

            'specialist_role' => $specialistRole,
            'gender' => (string) ($entry['gender'] ?? ''),
            'languages' => is_array($entry['languages'] ?? null) ? $entry['languages'] : [],
            'speciality_ids' => $specialityIdStrings,

            'doctor_database_id' => $explicitDoctorId
                ?? $fallbackDoctorId
                ?? self::fallbackDoctorIdFromDatabase(),
        ];
    }

    private static function normaliseOptionalDoctorId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private static function fallbackDoctorIdFromDatabase(): ?int
    {
        $id = Doctor::query()
            ->where('status', 'approved')
            ->orderBy('id')
            ->value('id');

        return self::normaliseOptionalDoctorId($id);
    }
}
