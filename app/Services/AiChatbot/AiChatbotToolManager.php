<?php

namespace App\Services\AiChatbot;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Faq;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use App\Support\AppTimezone;
use Illuminate\Support\Facades\Auth;

final class AiChatbotToolManager
{
    public function __construct(
        private readonly AiTherapistRecommendationService $recommendations,
        private readonly DoctorAvailabilityService $availability,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'searchTherapists',
                    'description' => 'Search and recommend licensed therapists based on the user needs. Returns top matches.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'User description of their concern'],
                            'specialty' => ['type' => 'string', 'description' => 'Optional specialty focus'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'findNearestAppointment',
                    'description' => 'Find the nearest available appointment slot for a therapist.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'doctor_id' => ['type' => 'integer', 'description' => 'Therapist ID'],
                            'duration_minutes' => ['type' => 'integer', 'description' => 'Session duration in minutes'],
                        ],
                        'required' => ['doctor_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'bookAppointment',
                    'description' => 'Guide the user to book an appointment with a therapist.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'doctor_id' => ['type' => 'integer'],
                            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                            'time' => ['type' => 'string', 'description' => 'HH:MM'],
                        ],
                        'required' => ['doctor_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'cancelAppointment',
                    'description' => 'Help the user cancel an upcoming appointment.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'appointment_id' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'searchFAQ',
                    'description' => 'Search frequently asked questions about the Awaan platform.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function execute(string $name, array $arguments): string
    {
        $result = match ($name) {
            'searchTherapists' => $this->searchTherapists($arguments),
            'findNearestAppointment' => $this->findNearestAppointment($arguments),
            'bookAppointment' => $this->bookAppointment($arguments),
            'cancelAppointment' => $this->cancelAppointment($arguments),
            'searchFAQ' => $this->searchFAQ($arguments),
            default => ['error' => 'Unknown tool'],
        };

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function searchTherapists(array $arguments): array
    {
        $results = $this->recommendations->searchTherapists(
            query: isset($arguments['query']) ? (string) $arguments['query'] : null,
            specialty: isset($arguments['specialty']) ? (string) $arguments['specialty'] : null,
        );

        return [
            'therapists' => $results,
            'count' => count($results),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function findNearestAppointment(array $arguments): array
    {
        $doctor = Doctor::query()
            ->where('status', 'approved')
            ->find((int) ($arguments['doctor_id'] ?? 0));

        if (! $doctor instanceof Doctor) {
            return ['error' => 'Therapist not found'];
        }

        $durationMinutes = max(15, (int) ($arguments['duration_minutes'] ?? 30));
        $doctor->loadMissing('durations');
        $offered = $doctor->durations->pluck('duration')->map(static fn ($d): int => (int) $d)->all();

        if ($offered !== [] && ! in_array($durationMinutes, $offered, true)) {
            $durationMinutes = (int) min($offered);
        }

        $timezone = AppTimezone::name();
        $today = now()->timezone($timezone)->startOfDay();

        for ($offset = 0; $offset < 14; $offset++) {
            $date = $today->copy()->addDays($offset)->toDateString();
            $slots = $this->availability->availableSlots($doctor, $date, $durationMinutes);

            if ($slots !== []) {
                return [
                    'doctor_id' => $doctor->id,
                    'doctor_name' => $doctor->displayName(),
                    'date' => $date,
                    'time' => $slots[0],
                    'duration_minutes' => $durationMinutes,
                    'booking_url' => route('patient.book-appointments', ['doctor' => $doctor->id]),
                ];
            }
        }

        return [
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->displayName(),
            'message' => 'No slots found in the next 14 days',
            'booking_url' => route('patient.book-appointments', ['doctor' => $doctor->id]),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function bookAppointment(array $arguments): array
    {
        $doctorId = (int) ($arguments['doctor_id'] ?? 0);
        $doctor = Doctor::query()->where('status', 'approved')->find($doctorId);

        if (! $doctor instanceof Doctor) {
            return ['error' => 'Therapist not found'];
        }

        $nearest = $this->findNearestAppointment([
            'doctor_id' => $doctorId,
            'duration_minutes' => $arguments['duration_minutes'] ?? 30,
        ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return [
                'requires_login' => true,
                'login_url' => route('patient.phone'),
                'suggested_slot' => $nearest,
                'message' => 'Please sign in to complete booking.',
            ];
        }

        return [
            'requires_login' => false,
            'booking_url' => route('patient.book-appointments', ['doctor' => $doctorId]),
            'suggested_slot' => $nearest,
            'message' => 'Open the booking page to confirm date and payment.',
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function cancelAppointment(array $arguments): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [
                'requires_login' => true,
                'login_url' => route('patient.phone'),
                'message' => 'Sign in to manage your appointments.',
            ];
        }

        $appointmentQuery = Appointment::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['new', 'rescheduled', 'pending_follow_up']);

        if (isset($arguments['appointment_id'])) {
            $appointmentQuery->whereKey((int) $arguments['appointment_id']);
        }

        $appointment = $appointmentQuery->orderBy('scheduled_at')->first();

        if (! $appointment instanceof Appointment) {
            return [
                'message' => 'No upcoming appointment found to cancel.',
                'appointments_url' => route('patient.appointments'),
            ];
        }

        return [
            'message' => 'Please cancel from your appointments page to complete refunds if applicable.',
            'appointment_id' => $appointment->id,
            'appointment_number' => $appointment->appointment_number,
            'scheduled_at' => optional($appointment->scheduled_at)->toIso8601String(),
            'appointments_url' => route('patient.appointments'),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function searchFAQ(array $arguments): array
    {
        $query = trim((string) ($arguments['query'] ?? ''));

        $faqs = Faq::query()
            ->where('is_active', true)
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($inner) use ($query): void {
                    $inner->where('question', 'like', '%'.$query.'%')
                        ->orWhere('question_ar', 'like', '%'.$query.'%')
                        ->orWhere('answer', 'like', '%'.$query.'%')
                        ->orWhere('answer_ar', 'like', '%'.$query.'%')
                        ->orWhere('category', 'like', '%'.$query.'%');
                });
            })
            ->orderBy('sort_order')
            ->limit(5)
            ->get()
            ->map(fn (Faq $faq): array => [
                'question' => $faq->questionDisplay(),
                'answer' => $faq->answerDisplay(),
                'category' => $faq->category,
            ])
            ->all();

        return [
            'faqs' => $faqs,
            'count' => count($faqs),
        ];
    }
}
