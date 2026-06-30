<?php

namespace App\Services\AiChatbot;

use App\Models\Doctor;
use App\Models\Speciality;
use Illuminate\Support\Str;

final class AiTherapistRecommendationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function searchTherapists(?string $query = null, ?string $specialty = null, int $limit = 3): array
    {
        $description = trim(collect([$query, $specialty])->filter()->implode(' '));

        if ($description === '') {
            $description = 'mental health support';
        }

        return $this->recommend($description, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recommend(string $description, int $limit = 3): array
    {
        $keywords = $this->extractKeywords($description);
        $specialityIds = $this->matchingSpecialityIds($keywords);

        return Doctor::query()
            ->where('status', 'approved')
            ->with(['degree', 'specialities', 'durations'])
            ->get()
            ->map(function (Doctor $doctor) use ($keywords, $specialityIds): array {
                $score = 0;

                if ($doctor->is_online) {
                    $score += 3;
                }

                $score += min(10, (int) ($doctor->experience ?? 0));

                $haystack = Str::lower(implode(' ', [
                    (string) $doctor->name,
                    (string) $doctor->name_ar,
                    (string) $doctor->about,
                    (string) $doctor->about_ar,
                    $doctor->specialities->pluck('title')->implode(' '),
                    $doctor->specialities->pluck('title_ar')->implode(' '),
                ]));

                foreach ($keywords as $keyword) {
                    if ($keyword !== '' && str_contains($haystack, $keyword)) {
                        $score += 4;
                    }
                }

                $doctorSpecialityIds = $doctor->specialities
                    ->pluck('id')
                    ->map(static fn (mixed $id): string => (string) $id)
                    ->all();

                if ($specialityIds !== [] && array_intersect($specialityIds, $doctorSpecialityIds) !== []) {
                    $score += 8;
                }

                return [
                    'doctor' => $doctor,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->map(function (array $row): array {
                /** @var Doctor $doctor */
                $doctor = $row['doctor'];

                return [
                    'id' => $doctor->id,
                    'name' => $doctor->displayName(),
                    'specialty' => $doctor->specialities->first()?->title ?? '',
                    'experience_years' => (int) ($doctor->experience ?? 0),
                    'is_online' => (bool) $doctor->is_online,
                    'languages' => match ((string) ($doctor->spoken_languages ?? '')) {
                        'ar' => ['ar'],
                        'en' => ['en'],
                        default => ['ar', 'en'],
                    },
                    'booking_url' => route('patient.book-appointments', ['doctor' => $doctor->id]),
                    'recommendation_score' => $row['score'],
                ];
            })
            ->all();
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $description): array
    {
        $normalized = Str::lower($description);

        $map = [
            'قلق' => ['anxiety', 'stress', 'worry'],
            'توتر' => ['anxiety', 'stress'],
            'اكتئاب' => ['depression', 'sadness'],
            'حزن' => ['depression', 'grief'],
            'علاق' => ['relationship', 'family', 'marriage'],
            'أسر' => ['family'],
            'نوم' => ['sleep', 'insomnia'],
        ];

        $keywords = preg_split('/[\s,،.]+/u', $normalized) ?: [];

        foreach ($map as $needle => $extras) {
            if (str_contains($normalized, $needle)) {
                $keywords = array_merge($keywords, $extras);
            }
        }

        return array_values(array_unique(array_filter($keywords, static fn (string $word): bool => mb_strlen($word) >= 3)));
    }

    /**
     * @param  list<string>  $keywords
     * @return list<string>
     */
    private function matchingSpecialityIds(array $keywords): array
    {
        if ($keywords === []) {
            return [];
        }

        return Speciality::query()
            ->where('status', true)
            ->where(function ($query) use ($keywords): void {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'like', '%'.$keyword.'%')
                        ->orWhere('title_ar', 'like', '%'.$keyword.'%');
                }
            })
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();
    }
}
