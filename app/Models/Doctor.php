<?php

namespace App\Models;

use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Doctor extends Authenticatable
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'name_ar',
        'email',
        'phone',
        'password',
        'verification_code',
        'profile_photo_path',
        'gender',
        'profile_detail_path',
        'signature',
        'registration_number',
        'degree_id',
        'speciality_id',
        'experience',
        'medical_career_level',
        'about',
        'about_ar',
        'spoken_languages',
        'is_online',
        'profile_completed',
        'accept_instant_appointment',
        'commission',
        'status',
        'active_status',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_completed' => 'boolean',
            'active_status' => 'boolean',
            'is_online' => 'boolean',
            'accept_instant_appointment' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Duration, $this>
     */
    public function durations(): BelongsToMany
    {
        return $this->belongsToMany(Duration::class, 'doctor_duration', 'doctor_id', 'duration')
            ->withPivot('price');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function displayName(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->name_ar)) {
            return (string) $this->name_ar;
        }

        return (string) ($this->name ?? $this->name_ar ?? '');
    }

    public function aboutDisplay(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->about_ar)) {
            return (string) $this->about_ar;
        }

        return (string) ($this->about ?? $this->about_ar ?? '');
    }

    public function profilePhotoUrl(): ?string
    {
        if (! filled($this->profile_photo_path)) {
            return null;
        }

        if (str_starts_with((string) $this->profile_photo_path, 'http://') || str_starts_with((string) $this->profile_photo_path, 'https://')) {
            return (string) $this->profile_photo_path;
        }

        return Storage::disk('public')->url($this->profile_photo_path);
    }
}
