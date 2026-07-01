<?php

namespace App\Services\AiChatbot;

use App\Models\Degree;
use App\Models\Doctor;
use App\Models\Speciality;
use App\Services\DoctorAvailabilityService;
use App\Support\AppTimezone;
use App\Support\SpecialistCatalog;
use Illuminate\Support\Facades\Session;

final class AiChatbotBookingFlowService
{
    /** @var list<string> */
    public const STEPS = [
        'degree',
        'speciality',
        'duration',
        'gender',
        'language',
        'doctors',
        'confirm',
    ];

    public function __construct(
        private readonly DoctorAvailabilityService $availability,
    ) {}

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function resolve(string $step, array $preferences, string $locale = 'ar'): array
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);

        return match ($step) {
            'degree' => $this->degreeStep($preferences),
            'speciality' => $this->specialityStep($preferences),
            'duration' => $this->durationStep($preferences),
            'gender' => $this->genderStep($preferences),
            'language' => $this->languageStep($preferences),
            'doctors' => $this->doctorsStep($preferences),
            'confirm' => $this->confirmStep($preferences),
            default => $this->degreeStep($preferences),
        };
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function complete(array $preferences, string $locale = 'ar'): array
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);

        $snapshot = $this->normalizedPreferences($preferences);
        Session::put('session_filter_preferences', $snapshot);

        $doctorId = (int) ($snapshot['doctor_id'] ?? 0);
        $durationMinutes = max(15, (int) ($snapshot['duration_minutes'] ?? 30));

        $nearestSlot = null;
        $bookingUrl = route('patient.schedule.specialists');

        if ($doctorId > 0) {
            $bookingUrl = route('patient.book-appointments', ['doctor' => $doctorId]);
            $nearestSlot = $this->findNearestSlot($doctorId, $durationMinutes);
        }

        return [
            'preferences' => $snapshot,
            'filter_url' => route('patient.schedule.filter'),
            'specialists_url' => route('patient.schedule.specialists'),
            'booking_url' => $bookingUrl,
            'nearest_slot' => $nearestSlot,
            'message' => __('ai_chatbot.booking.complete_message'),
        ];
    }

    public function nextStep(string $currentStep): ?string
    {
        $index = array_search($currentStep, self::STEPS, true);

        if ($index === false || $index >= count(self::STEPS) - 1) {
            return null;
        }

        return self::STEPS[$index + 1];
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function degreeStep(array $preferences): array
    {
        $options = Degree::query()
            ->where('status', true)
            ->orderBy('id')
            ->get(['id', 'title', 'title_ar'])
            ->map(fn (Degree $degree): array => [
                'id' => (string) $degree->id,
                'label' => $this->localizedTitle($degree->title, $degree->title_ar),
            ])
            ->values()
            ->all();

        if ($options === []) {
            $options = collect(__('session_filter.sections.specialist_kind'))
                ->map(fn (string $label, string $key): array => [
                    'id' => $key,
                    'label' => $label,
                ])
                ->values()
                ->all();
        }

        return $this->stepPayload(
            step: 'degree',
            prompt: __('ai_chatbot.booking.steps.degree'),
            mode: 'single',
            options: $options,
            preferences: $preferences,
            nextStep: 'speciality',
        );
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function specialityStep(array $preferences): array
    {
        $options = Speciality::query()
            ->where('status', true)
            ->orderBy('id')
            ->get(['id', 'title', 'title_ar'])
            ->map(fn (Speciality $speciality): array => [
                'id' => (string) $speciality->id,
                'label' => $this->localizedTitle($speciality->title, $speciality->title_ar),
            ])
            ->values()
            ->all();

        return $this->stepPayload(
            step: 'speciality',
            prompt: __('ai_chatbot.booking.steps.speciality'),
            mode: 'multi',
            options: $options,
            preferences: $preferences,
            nextStep: 'duration',
            allowSkip: true,
        );
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function durationStep(array $preferences): array
    {
        $options = collect(['15', '30', '45', '60'])
            ->map(fn (string $minutes): array => [
                'id' => $minutes,
                'label' => __('session_filter.sections.minutes.'.$minutes),
            ])
            ->all();

        return $this->stepPayload(
            step: 'duration',
            prompt: __('ai_chatbot.booking.steps.duration'),
            mode: 'single',
            options: $options,
            preferences: $preferences,
            nextStep: 'gender',
        );
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function genderStep(array $preferences): array
    {
        $options = collect(['male', 'female', 'both'])
            ->map(fn (string $value): array => [
                'id' => $value,
                'label' => __('session_filter.sections.gender.'.$value),
            ])
            ->all();

        return $this->stepPayload(
            step: 'gender',
            prompt: __('ai_chatbot.booking.steps.gender'),
            mode: 'single',
            options: $options,
            preferences: $preferences,
            nextStep: 'language',
        );
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function languageStep(array $preferences): array
    {
        $options = collect(['ar', 'en', 'both'])
            ->map(fn (string $value): array => [
                'id' => $value,
                'label' => __('session_filter.sections.lang.'.$value),
            ])
            ->all();

        return $this->stepPayload(
            step: 'language',
            prompt: __('ai_chatbot.booking.steps.language'),
            mode: 'single',
            options: $options,
            preferences: $preferences,
            nextStep: 'doctors',
        );
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function doctorsStep(array $preferences): array
    {
        $snapshot = $this->normalizedPreferences($preferences);
        $doctors = array_slice(SpecialistCatalog::filtered($snapshot), 0, 5);

        $doctorOptions = array_map(function (array $doctor): array {
            return [
                'id' => (string) ($doctor['doctor_database_id'] ?? ''),
                'label' => (string) ($doctor['name'] ?? ''),
                'photo_url' => (string) ($doctor['photo_url'] ?? ''),
                'degree_title' => (string) ($doctor['degree_title'] ?? ''),
                'tags' => array_slice((array) ($doctor['tags'] ?? []), 0, 3),
                'is_online' => (bool) ($doctor['is_online'] ?? false),
                'price_sar' => (int) ($doctor['price_sar'] ?? 0),
                'session_minutes' => (int) ($doctor['session_minutes'] ?? 0),
            ];
        }, $doctors);

        return $this->stepPayload(
            step: 'doctors',
            prompt: __('ai_chatbot.booking.steps.doctors', ['count' => count($doctorOptions)]),
            mode: 'doctors',
            options: [],
            preferences: $preferences,
            nextStep: 'confirm',
            doctors: $doctorOptions,
            allowSkip: true,
            specialistsUrl: route('patient.schedule.specialists'),
        );
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function confirmStep(array $preferences): array
    {
        $result = $this->complete($preferences, app()->getLocale());

        return [
            'step' => 'confirm',
            'prompt' => (string) ($result['message'] ?? ''),
            'mode' => 'link',
            'options' => [],
            'doctors' => [],
            'preferences' => $result['preferences'],
            'next_step' => null,
            'allow_skip' => false,
            'booking_url' => $result['booking_url'],
            'specialists_url' => $result['specialists_url'],
            'filter_url' => $result['filter_url'],
            'nearest_slot' => $result['nearest_slot'],
            'link_label' => __('ai_chatbot.booking.continue_booking'),
        ];
    }

    /**
     * @param  list<array{id: string, label: string}>  $options
     * @param  list<array<string, mixed>>  $doctors
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function stepPayload(
        string $step,
        string $prompt,
        string $mode,
        array $options,
        array $preferences,
        ?string $nextStep,
        array $doctors = [],
        bool $allowSkip = false,
        ?string $specialistsUrl = null,
    ): array {
        return [
            'step' => $step,
            'prompt' => $prompt,
            'mode' => $mode,
            'options' => $options,
            'doctors' => $doctors,
            'preferences' => $this->normalizedPreferences($preferences),
            'next_step' => $nextStep,
            'allow_skip' => $allowSkip,
            'skip_label' => $allowSkip ? __('ai_chatbot.booking.skip_step') : null,
            'continue_label' => $mode === 'multi' ? __('ai_chatbot.booking.continue') : null,
            'specialists_url' => $specialistsUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function normalizedPreferences(array $preferences): array
    {
        $subspecialties = collect($preferences['subspecialties'] ?? [])
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'degree_id' => isset($preferences['degree_id']) ? (string) $preferences['degree_id'] : '',
            'gender_preference' => isset($preferences['gender_preference']) ? (string) $preferences['gender_preference'] : '',
            'duration_minutes' => isset($preferences['duration_minutes']) ? (string) $preferences['duration_minutes'] : '',
            'language_preference' => isset($preferences['language_preference']) ? (string) $preferences['language_preference'] : '',
            'subspecialties' => $subspecialties,
            'doctor_id' => isset($preferences['doctor_id']) ? (int) $preferences['doctor_id'] : null,
        ];
    }

    /**
     * @return array{date: string, time: string}|null
     */
    private function findNearestSlot(int $doctorId, int $durationMinutes): ?array
    {
        $doctor = Doctor::query()
            ->where('status', 'approved')
            ->find($doctorId);

        if ($doctor === null) {
            return null;
        }

        $timezone = AppTimezone::name();
        $today = now()->timezone($timezone)->startOfDay();

        for ($offset = 0; $offset < 14; $offset++) {
            $date = $today->copy()->addDays($offset)->toDateString();
            $slots = $this->availability->availableSlots($doctor, $date, $durationMinutes);

            if ($slots !== []) {
                return [
                    'date' => $date,
                    'time' => $slots[0],
                ];
            }
        }

        return null;
    }

    private function localizedTitle(?string $title, ?string $titleAr): string
    {
        $isAr = app()->getLocale() === 'ar';

        if ($isAr) {
            return filled($titleAr) ? (string) $titleAr : (string) ($title ?? '');
        }

        return filled($title) ? (string) $title : (string) ($titleAr ?? '');
    }
}
