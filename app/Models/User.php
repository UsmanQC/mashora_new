<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Interfaces\WalletFloat;
use Bavix\Wallet\Traits\HasWalletFloat;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'gender',
    'birth_date',
    'profile_completed',
    'profile_photo_path',
    'verification_code',
    'status',
    'avatar',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'verification_code'])]
class User extends Authenticatable implements Wallet, WalletFloat
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasWalletFloat, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $v = $user->getAttribute('avatar');
            if ($v === null || $v === '') {
                $user->setAttribute(
                    'avatar',
                    (string) config('chatify.user_avatar.default', 'avatar.png'),
                );
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'profile_completed' => 'boolean',
            'status' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Mood check-ins logged from the patient portal.
     *
     * @return HasMany<PatientMood, $this>
     */
    public function patientMoods(): HasMany
    {
        return $this->hasMany(PatientMood::class);
    }

    /**
     * Appointments booked by this patient.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return MorphMany<Notification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'userable');
    }

    /**
     * @return MorphMany<Ticket, $this>
     */
    public function tickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'creator');
    }
}
