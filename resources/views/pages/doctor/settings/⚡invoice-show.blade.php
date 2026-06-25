<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Invoice')] class extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        abort_unless((int) $invoice->doctor_id === (int) $this->doctor()->id, 403);

        $this->invoice = $invoice->load([
            'appointments' => static fn ($query) => $query
                ->orderBy('appointment_date')
                ->orderBy('start_time'),
        ]);
    }

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function statusLabel(): string
    {
        return $this->invoice->isPaid()
            ? __('doctor.invoices.status_paid')
            : __('doctor.invoices.status_unpaid');
    }

    public function statusBadgeClasses(): string
    {
        return $this->invoice->isPaid()
            ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
            : 'bg-amber-100 text-amber-900 ring-amber-200';
    }

    public function formattedAppointmentDate(Appointment $appointment): string
    {
        if ($appointment->appointment_date === null) {
            return '—';
        }

        try {
            $date = Carbon::parse($appointment->appointment_date)
                ->locale(app()->getLocale())
                ->translatedFormat('d M Y');

            if (filled($appointment->start_time)) {
                $time = Carbon::createFromFormat('H:i:s', (string) $appointment->start_time)
                    ->format('H:i');

                return $date.' · '.$time;
            }

            return $date;
        } catch (\Throwable) {
            return (string) $appointment->appointment_date;
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-semibold text-zinc-900">
                {{ $invoice->reference ?: __('doctor.invoices.invoice_number', ['id' => $invoice->id]) }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500">
                {{ __('doctor.invoices.detail_subtitle') }}
            </flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span @class([
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
                $this->statusBadgeClasses(),
            ])>
                {{ $this->statusLabel() }}
            </span>
            <flux:button
                :href="route('doctor.settings.invoices.pdf', $invoice)"
                icon="arrow-down-tray"
                variant="primary"
                class="!bg-[#10B981] !text-white"
            >
                {{ __('doctor.invoices.download_pdf') }}
            </flux:button>
            <flux:button :href="route('doctor.settings.invoices')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('doctor.invoices.back_to_list') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('doctor.invoices.period') }}</p>
            <p class="mt-1 text-sm font-semibold text-zinc-900">
                @if ($invoice->from_date && $invoice->to_date)
                    {{ $invoice->from_date->format('Y-m-d') }} → {{ $invoice->to_date->format('Y-m-d') }}
                @else
                    —
                @endif
            </p>
        </div>
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('doctor.invoices.issued') }}</p>
            <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $invoice->issue_date?->format('Y-m-d') ?? '—' }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('doctor.invoices.doctor_share') }}</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-[#10B981]">
                {{ number_format((float) $invoice->doctor_share, 2) }}
                <span class="text-sm font-semibold">{{ config('currency.sa_riyal_symbol') }}</span>
            </p>
        </div>
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('doctor.invoices.payment_status') }}</p>
            <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->statusLabel() }}</p>
            @if ($invoice->isPaid() && $invoice->paid_at)
                <p class="mt-0.5 text-xs text-zinc-500">{{ $invoice->paid_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-sm">
        <div class="border-b border-zinc-100 px-5 py-4">
            <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('doctor.invoices.sessions_heading') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">{{ __('doctor.invoices.sessions_subtitle') }}</flux:text>
        </div>

        @if ($invoice->appointments->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-zinc-500">{{ __('doctor.invoices.no_sessions') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">{{ __('doctor.invoices.col_reference') }}</th>
                            <th class="px-5 py-3">{{ __('doctor.invoices.col_date') }}</th>
                            <th class="px-5 py-3">{{ __('doctor.invoices.col_patient') }}</th>
                            <th class="px-5 py-3">{{ __('doctor.invoices.col_phone') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('doctor.invoices.col_total') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('doctor.invoices.col_doctor_share') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($invoice->appointments as $appointment)
                            <tr class="hover:bg-zinc-50/80">
                                <td class="px-5 py-3 font-medium text-zinc-900">#{{ $appointment->appointment_number ?: $appointment->id }}</td>
                                <td class="px-5 py-3 text-zinc-700">{{ $this->formattedAppointmentDate($appointment) }}</td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-zinc-900">{{ $appointment->patient_name ?: '—' }}</div>
                                    @if (filled($appointment->patient_email))
                                        <div class="text-xs text-zinc-500">{{ $appointment->patient_email }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-zinc-700">{{ $appointment->patient_phone ?: '—' }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-zinc-900">{{ number_format((float) $appointment->total, 2) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-semibold text-[#10B981]">{{ number_format((float) $appointment->doctor_share, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-50 text-sm font-semibold text-zinc-900">
                        <tr>
                            <td colspan="4" class="px-5 py-3">{{ __('doctor.invoices.total_amount') }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-[#10B981]">{{ number_format((float) $invoice->doctor_share, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
