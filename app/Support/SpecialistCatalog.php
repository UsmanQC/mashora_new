<?php

namespace App\Support;

use App\Models\Communication;
use App\Models\Degree;
use App\Models\Doctor;
use App\Models\Speciality;
use Carbon\Carbon;

final class SpecialistCatalog
{
    /**
     * @return list<array<string, mixed>> Card-shaped rows saved by the patient.
     */
    public static function favoritedDoctorCardsForUser(int $userId): array
    {
        $doctorIds = DoctorFavorites::likedDoctorIdsForUser($userId);

        if ($doctorIds === []) {
            return [];
        }

        $order = array_flip($doctorIds);

        $doctors = Doctor::query()
            ->where('status', 'approved')
            ->whereIn('id', $doctorIds)
            ->withCount('likes')
            ->with(['degree', 'specialities', 'durations', 'workingDays.workingHours', 'communications'])
            ->get()
            ->sortBy(static fn (Doctor $doctor): int => $order[$doctor->id] ?? PHP_INT_MAX)
            ->values();

        return $doctors
            ->map(static fn (Doctor $doctor): array => self::toDoctorCardShape($doctor))
            ->all();
    }

    /**
     * @return list<array<string, mixed>> Card-shaped rows for patient-specialist-result-card
     */
    public static function all(): array
    {
        $doctorCards = self::approvedDoctorCards();
        if ($doctorCards !== []) {
            return $doctorCards;
        }

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
        $roleWant = $preferences['degree_id'] ?? ($preferences['specialist_role'] ?? null);
        if (is_string($roleWant) && $roleWant !== '' && $roleWant !== 'all') {
            $docDegreeId = (string) ($doc['degree_id'] ?? '');

            if (is_numeric($roleWant)) {
                if ($docDegreeId === '' || $docDegreeId !== $roleWant) {
                    return false;
                }
            } elseif (($doc['specialist_role'] ?? '') !== $roleWant) {
                return false;
            }
        }

        $genderPref = $preferences['gender_preference'] ?? 'both';
        if (in_array($genderPref, ['male', 'female'], true) && ($doc['gender'] ?? '') !== $genderPref) {
            return false;
        }

        $durationWant = isset($preferences['duration_minutes']) ? (string) $preferences['duration_minutes'] : '';
        if ($durationWant !== '' && ! self::offersDuration($doc, $durationWant)) {
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
     * @param  array<string, mixed>  $doc
     */
    public static function offersDuration(array $doc, string $minutes): bool
    {
        return in_array($minutes, self::offeredDurationMinutes($doc), true);
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return list<string>
     */
    public static function offeredDurationMinutes(array $doc): array
    {
        if (is_array($doc['offered_duration_minutes'] ?? null) && $doc['offered_duration_minutes'] !== []) {
            return array_values(array_map(static fn (mixed $minutes): string => (string) $minutes, $doc['offered_duration_minutes']));
        }

        $sessionMinutes = (string) ($doc['session_minutes'] ?? '');

        return $sessionMinutes !== '' ? [$sessionMinutes] : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function approvedCards(): array
    {
        return self::approvedDoctorCards();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forMarketingHomepage(int $limit = 20): array
    {
        return array_slice(self::approvedDoctorCards(), 0, max(0, $limit));
    }

    /**
     * @return array{total: int, online: int}
     */
    public static function marketingStats(): array
    {
        $baseQuery = Doctor::query()->where('status', 'approved');

        return [
            'total' => (clone $baseQuery)->count(),
            'online' => (clone $baseQuery)->where('is_online', true)->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function approvedDoctorCards(): array
    {
        $doctors = Doctor::query()
            ->where('status', 'approved')
            ->withCount('likes')
            ->with(['degree', 'specialities', 'durations', 'workingDays.workingHours', 'communications'])
            ->orderByDesc('is_online')
            ->orderBy('id')
            ->get();

        if ($doctors->isEmpty()) {
            return [];
        }

        return $doctors
            ->map(static fn (Doctor $doctor): array => self::toDoctorCardShape($doctor))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function toDoctorCardShape(Doctor $doctor): array
    {
        $degreeTitle = (string) ($doctor->degree?->title ?? '');
        $roleKind = str_contains(strtolower($degreeTitle), 'non-doctor')
            ? 'therapist'
            : 'physician_specialist';

        $offeredDurations = $doctor->durations->sortBy('duration')->values();
        /** @var list<string> $offeredDurationMinutes */
        $offeredDurationMinutes = $offeredDurations
            ->pluck('duration')
            ->map(static fn (mixed $minutes): string => (string) $minutes)
            ->values()
            ->all();

        $preferredDuration = (string) (session('session_filter_preferences.duration_minutes') ?? '');
        $selectedDuration = $offeredDurations->first();

        if ($preferredDuration !== '' && $offeredDurations->contains('duration', (int) $preferredDuration)) {
            $selectedDuration = $offeredDurations->firstWhere('duration', (int) $preferredDuration);
        }

        $sessionMinutes = (int) ($selectedDuration?->duration ?? 15);
        $price = (int) round((float) ($selectedDuration?->pivot?->price ?? 0));

        $language = (string) ($doctor->spoken_languages ?? '');
        $languages = match ($language) {
            'ar' => ['ar'],
            'en' => ['en'],
            default => ['ar', 'en'],
        };

        /** @var list<string> $specialityIds */
        $specialityIds = $doctor->specialities
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->values()
            ->all();

        $isAr = app()->getLocale() === 'ar';
        /** @var list<string> $tags */
        $tags = $doctor->specialities
            ->map(function (Speciality $speciality) use ($isAr): string {
                return $isAr
                    ? (filled($speciality->title_ar) ? (string) $speciality->title_ar : (string) $speciality->title)
                    : (filled($speciality->title) ? (string) $speciality->title : (string) $speciality->title_ar);
            })
            ->values()
            ->all();

        /** @var list<string> $communicationCodes */
        $communicationCodes = $doctor->communications
            ->pluck('communication')
            ->map(static fn (mixed $code): string => (string) $code)
            ->all();

        if ($communicationCodes === []) {
            $communicationCodes = Communication::query()
                ->pluck('communication')
                ->map(static fn (mixed $code): string => (string) $code)
                ->all();
        }

        $channels = [
            'chat' => in_array('chat', $communicationCodes, true),
            'video' => in_array('video_call', $communicationCodes, true),
            'voice' => in_array('voice_call', $communicationCodes, true),
        ];

        /** @var list<string> $slots */
        $slots = collect($doctor->workingDays)
            ->where('is_working', true)
            ->flatMap(static function ($workingDay) {
                return $workingDay->workingHours->flatMap(static function ($hour) {
                    if (! filled($hour->start_time) || ! filled($hour->end_time)) {
                        return [];
                    }

                    $start = Carbon::createFromFormat('H:i:s', (string) $hour->start_time);
                    $end = Carbon::createFromFormat('H:i:s', (string) $hour->end_time);
                    $times = [];

                    while ($start < $end) {
                        $times[] = $start->format('H:i');
                        $start = $start->addMinutes(15);
                    }

                    return $times;
                });
            })
            ->unique()
            ->take(8)
            ->values()
            ->all();

        $degreeTitle = '';
        if ($doctor->degree) {
            $degreeTitle = $isAr && filled($doctor->degree->title_ar)
                ? (string) $doctor->degree->title_ar
                : (string) (filled($doctor->degree->title) ? $doctor->degree->title : $doctor->degree->title_ar);
        }

        return [
            'id' => 'doctor-'.$doctor->id,
            'name' => $doctor->displayName(),
            'bio' => $doctor->aboutDisplay(),
            'photo_url' => $doctor->profilePhotoUrl(),
            'degree_title' => $degreeTitle,
            'is_online' => (bool) $doctor->is_online,
            'accept_instant_appointment' => $doctor->accept_instant_appointment !== false,
            'experience_years' => (int) ($doctor->experience ?? 0),
            'role_kind' => $roleKind,
            'likes' => (int) ($doctor->likes_count ?? 0),
            'price_sar' => $price,
            'session_minutes' => $sessionMinutes,
            'offered_duration_minutes' => $offeredDurationMinutes,
            'channels' => $channels,
            'slots' => $slots,
            'tags' => $tags,
            'specialist_role' => '',
            'degree_id' => $doctor->degree_id !== null ? (string) $doctor->degree_id : null,
            'gender' => (string) ($doctor->gender ?? ''),
            'languages' => $languages,
            'speciality_ids' => $specialityIds,
            'doctor_database_id' => $doctor->id,
        ];
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
        $degreeId = self::resolveDegreeIdFromLegacyRole($specialistRole);

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
            'offered_duration_minutes' => [(string) ($entry['session_minutes'] ?? 15)],
            'channels' => is_array($entry['channels'] ?? null) ? $entry['channels'] : [],
            'slots' => is_array($entry['slots'] ?? null) ? $entry['slots'] : [],
            'tags' => $tags,

            'specialist_role' => $specialistRole,
            'degree_id' => $degreeId !== null ? (string) $degreeId : null,
            'gender' => (string) ($entry['gender'] ?? ''),
            'languages' => is_array($entry['languages'] ?? null) ? $entry['languages'] : [],
            'speciality_ids' => $specialityIdStrings,

            'doctor_database_id' => $explicitDoctorId
                ?? $fallbackDoctorId
                ?? self::fallbackDoctorIdFromDatabase(),
            'accept_instant_appointment' => (bool) ($entry['accept_instant_appointment'] ?? true),
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

    private static function resolveDegreeIdFromLegacyRole(string $legacyRole): ?int
    {
        $degreeTitle = match ($legacyRole) {
            'psychiatrist' => 'Doctor (Specialist)',
            'consultant' => 'Doctor (Consultant)',
            'psychologist_non_md' => 'Non-Doctor (Therapist)',
            default => null,
        };

        if ($degreeTitle === null) {
            return null;
        }

        $id = Degree::query()
            ->where('status', true)
            ->where('title', $degreeTitle)
            ->value('id');

        return self::normaliseOptionalDoctorId($id);
    }
}
