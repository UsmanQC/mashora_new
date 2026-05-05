<?php

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::doctor')] #[Title('Appointments')] class extends Component
{
    use WithPagination;

    public function getAppointmentsProperty(): LengthAwarePaginator
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(12);
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.appointments.title') }}</flux:heading>

    @if ($this->appointments->isEmpty())
        <flux:text class="text-zinc-600">{{ __('doctor.appointments.empty') }}</flux:text>
    @else
        <div class="overflow-x-auto rounded-2xl border border-zinc-200/90 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-zinc-100 text-sm">
                <thead class="bg-zinc-50 text-start text-xs font-semibold uppercase text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('doctor.appointments.patient') }}</th>
                        <th class="px-4 py-3">{{ __('doctor.appointments.date') }}</th>
                        <th class="px-4 py-3">{{ __('doctor.appointments.time') }}</th>
                        <th class="px-4 py-3">{{ __('doctor.appointments.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($this->appointments as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-900">{{ $row->patient_name }}</td>
                            <td class="px-4 py-3 tabular-nums text-zinc-700">{{ $row->appointment_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 tabular-nums text-zinc-700">{{ \Illuminate\Support\Str::limit((string) $row->start_time, 8, '') }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $row->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-1">
            {{ $this->appointments->links() }}
        </div>
    @endif
</div>
