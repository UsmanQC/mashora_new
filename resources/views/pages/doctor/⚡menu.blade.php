<?php

use App\Models\Doctor;
use App\Models\Notification;
use App\Support\DoctorMenu;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Menu')] class extends Component
{
    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    #[Computed]
    public function currentDoctor(): Doctor
    {
        return Doctor::query()
            ->whereKey($this->doctor()->id)
            ->with(['specialities', 'degree'])
            ->firstOrFail();
    }

    #[Computed]
    public function specialityLabel(): string
    {
        $speciality = $this->currentDoctor->specialities->first();

        if ($speciality === null) {
            return '';
        }

        if (app()->getLocale() === 'ar' && filled($speciality->title_ar)) {
            return (string) $speciality->title_ar;
        }

        return (string) ($speciality->title ?? $speciality->title_ar ?? '');
    }

    #[Computed]
    public function profileSubtitle(): string
    {
        $parts = array_filter([
            $this->specialityLabel,
            filled($this->currentDoctor->medical_career_level)
                ? (string) $this->currentDoctor->medical_career_level
                : null,
        ]);

        return implode(' · ', $parts);
    }

    public function initialsFor(?string $name): string
    {
        if (! filled($name)) {
            return '?';
        }

        $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];

        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return mb_strtoupper(mb_substr((string) $name, 0, 2));
    }

    #[Computed]
    public function patientsCount(): int
    {
        return (int) $this->doctor()->appointments()
            ->whereNotNull('user_id')
            ->distinct()
            ->count('user_id');
    }

    #[Computed]
    public function prescriptionPendingCount(): int
    {
        return $this->doctor()->appointments()
            ->where('status', 'completed')
            ->where('prescription_not_needed', false)
            ->whereDoesntHave('medications')
            ->count();
    }

    #[Computed]
    public function unreadNotificationCount(): int
    {
        return Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->whereNull('read_at')
            ->count();
    }

    public function badgeValue(?string $key): ?string
    {
        return match ($key) {
            'patients_count' => number_format($this->patientsCount),
            'prescriptions_count' => $this->prescriptionPendingCount > 0
                ? trans_choice('doctor.mobile.more_prescriptions_pending', $this->prescriptionPendingCount, ['count' => $this->prescriptionPendingCount])
                : null,
            'notifications_count' => $this->unreadNotificationCount > 0
                ? (string) $this->unreadNotificationCount
                : null,
            default => null,
        };
    }

    /**
     * @return array<int, array{heading: string, items: array<int, array{label: string, sub: string, icon: string, route: string, available: bool, badge_key: string|null, query: array<string, string>}>}>
     */
    #[Computed]
    public function menuSections(): array
    {
        return DoctorMenu::mobileMoreSections();
    }
}; ?>

<div class="relative w-full">
    @include('partials.doctor-luxury-more-mobile')

    <div class="hidden space-y-8 lg:block">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="xl" class="font-semibold tracking-tight text-zinc-900">
                {{ __('doctor.menu.title') }}
            </flux:heading>
            <flux:text class="text-sm text-zinc-500">{{ __('doctor.menu.subtitle') }}</flux:text>
        </div>

        @include('partials.doctor-menu-sections')
    </div>
</div>
