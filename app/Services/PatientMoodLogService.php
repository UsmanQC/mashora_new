<?php

namespace App\Services;

use App\Models\PatientMood;
use App\Models\User;
use App\Support\PatientMoodImage;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class PatientMoodLogService
{
    public function todayDate(): CarbonInterface
    {
        return Carbon::instance(now()->timezone(config('app.timezone')))->startOfDay();
    }

    public function moodKeyForDate(User $user, CarbonInterface $date): ?string
    {
        $mood = PatientMood::query()
            ->where('user_id', $user->getKey())
            ->whereDate('date', $date->toDateString())
            ->orderByDesc('id')
            ->value('mood');

        return is_string($mood) && $mood !== '' ? $mood : null;
    }

    public function moodKeyForToday(User $user): ?string
    {
        return $this->moodKeyForDate($user, $this->todayDate());
    }

    public function hasMoodForDate(User $user, CarbonInterface $date): bool
    {
        return $this->moodKeyForDate($user, $date) !== null;
    }

    public function hasMoodForToday(User $user): bool
    {
        return $this->hasMoodForDate($user, $this->todayDate());
    }

    /**
     * @param  value-of<PatientMoodImage::MOOD_KEYS>  $moodKey
     */
    public function logMoodForToday(User $user, string $moodKey, ?string $comments = null, bool $isShared = false): ?PatientMood
    {
        if ($this->hasMoodForToday($user)) {
            return null;
        }

        return PatientMood::query()->create([
            'user_id' => $user->getKey(),
            'mood' => $moodKey,
            'comments' => $comments,
            'date' => $this->todayDate(),
            'is_shared' => $isShared,
        ]);
    }
}
