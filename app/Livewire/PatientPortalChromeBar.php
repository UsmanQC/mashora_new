<?php

namespace App\Livewire;

use App\Models\Notification;
use App\Models\User;
use App\Services\PatientMoodLogService;
use App\Support\PatientMoodImage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PatientPortalChromeBar extends Component
{
    public function openMoodPicker(): void
    {
        $this->dispatch('open-patient-mood-picker');
    }

    #[On('patient-mood-saved')]
    public function refreshTodayMood(): void
    {
        unset($this->todayMoodKey);
    }

    #[Computed]
    public function todayMoodKey(): ?string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return app(PatientMoodLogService::class)->moodKeyForToday($user);
    }

    #[Computed]
    public function todayMoodImageUrl(): ?string
    {
        $key = $this->todayMoodKey;

        return $key !== null ? PatientMoodImage::url($key) : null;
    }

    #[Computed]
    public function unreadNotificationCount(): int
    {
        return $this->notificationQuery()->whereNull('read_at')->count();
    }

    /**
     * @return Collection<int, Notification>
     */
    #[Computed]
    public function recentNotifications(): Collection
    {
        return $this->notificationQuery()
            ->latest()
            ->limit(5)
            ->get();
    }

    public function openNotification(int $notificationId): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $notification = Notification::query()
            ->whereKey($notificationId)
            ->where('userable_type', User::class)
            ->where('userable_id', $user->id)
            ->first();

        if ($notification === null) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        unset($this->unreadNotificationCount, $this->recentNotifications);

        $destination = filled($notification->action)
            ? (string) $notification->action
            : route('patient.notifications');

        $this->redirect($destination, navigate: true);
    }

    /**
     * @return Builder<Notification>
     */
    private function notificationQuery(): Builder
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return Notification::query()->whereRaw('0 = 1');
        }

        return Notification::query()
            ->where('userable_type', User::class)
            ->where('userable_id', $user->id);
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.patient-portal-chrome-bar', [
            'user' => $user instanceof User ? $user : null,
        ]);
    }
}
