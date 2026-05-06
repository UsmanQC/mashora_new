<?php

namespace App\Livewire\Doctor\Components;

use App\Models\Doctor;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

    public function readNotification(): void
    {
        Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotificationsProperty(): Collection
    {
        return Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function getUnreadCountProperty(): int
    {
        return Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->whereNull('read_at')
            ->count();
    }

    public function render()
    {
        return view('livewire.doctor.components.notifications');
    }
}
