<?php

namespace App\Models;

use App\Observers\DoctorObserver;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Interfaces\WalletFloat;
use Bavix\Wallet\Traits\HasWalletFloat;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([DoctorObserver::class])]
class Doctor extends Authenticatable implements Wallet, WalletFloat
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    use HasWalletFloat;
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
        'rejection_reason',
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

    /**
     * Patient favourites for this doctor.
     *
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * @return HasMany<WorkingDay, $this>
     */
    public function workingDays(): HasMany
    {
        return $this->hasMany(WorkingDay::class);
    }

    /**
     * @return BelongsToMany<Communication, $this>
     */
    public function communications(): BelongsToMany
    {
        return $this->belongsToMany(Communication::class, 'doctor_communication', 'doctor_id', 'communication');
    }

    /**
     * @return HasOne<BankAccount, $this>
     */
    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return MorphMany<Notification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'userable');
    }

    /**
     * @return MorphMany<DeviceToken, $this>
     */
    public function deviceTokens(): MorphMany
    {
        return $this->morphMany(DeviceToken::class, 'userable');
    }

    /**
     * @return MorphMany<Ticket, $this>
     */
    public function tickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'creator');
    }

    /**
     * @return BelongsTo<Degree, $this>
     */
    public function degree(): BelongsTo
    {
        return $this->belongsTo(Degree::class);
    }

    /**
     * @return BelongsToMany<Speciality, $this>
     */
    public function specialities(): BelongsToMany
    {
        return $this->belongsToMany(Speciality::class, 'doctor_speciality', 'doctor_id', 'speciality_id');
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

    public function profileDetailUrl(): ?string
    {
        if (! filled($this->profile_detail_path)) {
            return null;
        }

        if (str_starts_with((string) $this->profile_detail_path, 'http://') || str_starts_with((string) $this->profile_detail_path, 'https://')) {
            return (string) $this->profile_detail_path;
        }

        return Storage::disk('public')->url($this->profile_detail_path);
    }
}
