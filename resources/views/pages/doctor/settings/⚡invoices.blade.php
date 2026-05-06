<?php

use App\Models\Doctor;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Invoices')] class extends Component
{
    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function getInvoicesProperty()
    {
        return Invoice::query()
            ->where('doctor_id', $this->doctor()->id)
            ->latest()
            ->limit(50)
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('Invoices') }}</flux:heading>
        <flux:button :href="route('doctor.settings')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <div class="space-y-3">
            @forelse ($this->invoices as $invoice)
                <div class="rounded-xl border border-zinc-200 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <flux:text class="font-semibold text-zinc-900">{{ $invoice->reference ?: __('Invoice #:id', ['id' => $invoice->id]) }}</flux:text>
                            <flux:text class="text-xs text-zinc-500">{{ __('Issued: :date', ['date' => $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('Y-m-d') : '—']) }}</flux:text>
                        </div>
                        <div class="text-right">
                            <flux:text class="font-semibold text-zinc-900">{{ Number::format((float) $invoice->total_amount, 2) }} <img src="{{ asset('images/saudi_riyal.svg') }}" alt="Saudi Riyal" class="inline-block h-3 w-3 align-middle" /></flux:text>
                            <flux:text class="text-xs text-zinc-500">{{ ucfirst((string) $invoice->payment_status) }}</flux:text>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 text-center">
                    <flux:text class="text-sm text-zinc-500">{{ __('No invoices found yet.') }}</flux:text>
                </div>
            @endforelse
        </div>
    </div>
</div>
