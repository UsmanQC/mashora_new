<?php

namespace App\Livewire\Doctor\Components;

use App\Models\Doctor;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Notifications extends Component
{
    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    /**
     * @return Builder<Notification>
     */
    private function notificationQuery(): Builder
    {
        return Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return $this->notificationQuery()->whereNull('read_at')->count();
    }

    /**
     * @return Collection<int, Notification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return $this->notificationQuery()
            ->latest()
            ->limit(5)
            ->get();
    }

    public function openNotification(int $notificationId): void
    {
        $notification = $this->notificationQuery()
            ->whereKey($notificationId)
            ->first();

        if ($notification === null) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        unset($this->unreadCount, $this->notifications);

        $destination = filled($notification->action)
            ? (string) $notification->action
            : route('doctor.settings.notifications');

        $this->redirect($destination, navigate: true);
    }

    public function readNotification(): void
    {
        $this->notificationQuery()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        unset($this->unreadCount, $this->notifications);
    }

    public function render()
    {
        return view('livewire.doctor.components.notifications');
    }
}
