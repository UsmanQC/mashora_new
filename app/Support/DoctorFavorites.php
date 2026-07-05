<?php

namespace App\Support;

use App\Models\Like;

final class DoctorFavorites
{
    /**
     * Toggle favourite state for a patient and doctor.
     *
     * @return bool Whether the doctor is liked after the toggle
     */
    public static function toggle(int $userId, int $doctorId): bool
    {
        $existingLike = Like::query()
            ->where('user_id', $userId)
            ->where('doctor_id', $doctorId)
            ->first();

        if ($existingLike !== null) {
            $existingLike->delete();

            return false;
        }

        Like::query()->create([
            'user_id' => $userId,
            'doctor_id' => $doctorId,
        ]);

        return true;
    }

    /**
     * @return list<int> Doctor IDs ordered by most recently liked
     */
    public static function likedDoctorIdsForUser(int $userId): array
    {
        return Like::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->pluck('doctor_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
