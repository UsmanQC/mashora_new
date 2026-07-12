<?php



use App\Models\Doctor;

use App\Models\Invoice;

use Illuminate\Support\Facades\Auth;

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

            ->withCount('appointments')

            ->latest()

            ->limit(50)

            ->get();

    }



    public function statusLabel(Invoice $invoice): string

    {

        return $invoice->isPaid()

            ? __('doctor.invoices.status_paid')

            : __('doctor.invoices.status_unpaid');

    }



    public function statusBadgeClasses(Invoice $invoice): string

    {

        return $invoice->isPaid()

            ? 'bg-emerald-100 text-emerald-800'

            : 'bg-amber-100 text-amber-900';

    }

}; ?>



<div class="relative w-full">

    @include('partials.doctor-luxury-invoices-mobile')

    <div class="hidden space-y-6 lg:block">

    <div class="flex items-center justify-between gap-3">

        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.invoices.title') }}</flux:heading>

        <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('doctor.invoices.back') }}</flux:button>

    </div>



    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">

        <div class="space-y-3">

            @forelse ($this->invoices as $invoice)

                <div class="rounded-xl border border-zinc-200 p-4 transition hover:border-[#10B981]/30 hover:shadow-sm">

                    <div class="flex flex-wrap items-start justify-between gap-3">

                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-center gap-2">

                                <flux:text class="font-semibold text-zinc-900">

                                    {{ $invoice->reference ?: __('doctor.invoices.invoice_number', ['id' => $invoice->id]) }}

                                </flux:text>

                                <span @class([

                                    'inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide',

                                    $this->statusBadgeClasses($invoice),

                                ])>

                                    {{ $this->statusLabel($invoice) }}

                                </span>

                            </div>

                            <flux:text class="mt-1 text-xs text-zinc-500">

                                {{ __('doctor.invoices.issued') }}: {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('Y-m-d') : '—' }}

                            </flux:text>

                            @if ($invoice->from_date && $invoice->to_date)

                                <flux:text class="text-xs text-zinc-500">

                                    {{ __('doctor.invoices.period') }}: {{ $invoice->from_date->format('Y-m-d') }} → {{ $invoice->to_date->format('Y-m-d') }}

                                </flux:text>

                            @endif

                            <flux:text class="text-xs text-zinc-500">

                                {{ trans_choice('doctor.invoices.sessions_count', $invoice->appointments_count, ['count' => $invoice->appointments_count]) }}

                            </flux:text>

                        </div>

                        <div class="text-right">

                            <flux:text class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('doctor.invoices.doctor_share') }}</flux:text>

                            <flux:text class="text-lg font-bold tabular-nums text-[#10B981]">

                                {{ number_format((float) $invoice->doctor_share, 2) }}

                                <img src="{{ asset('images/saudi_riyal.svg') }}" alt="Saudi Riyal" class="inline-block h-3 w-3 align-middle" />

                            </flux:text>

                        </div>

                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">

                        <flux:button

                            :href="route('doctor.settings.invoices.show', $invoice)"

                            wire:navigate

                            size="sm"

                            variant="outline"

                            icon="eye"

                        >

                            {{ __('doctor.invoices.view_details') }}

                        </flux:button>

                        <flux:button

                            :href="route('doctor.settings.invoices.pdf', $invoice)"

                            size="sm"

                            variant="ghost"

                            icon="arrow-down-tray"

                        >

                            {{ __('doctor.invoices.download_pdf') }}

                        </flux:button>

                    </div>

                </div>

            @empty

                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 text-center">

                    <flux:text class="text-sm text-zinc-500">{{ __('doctor.invoices.empty') }}</flux:text>

                </div>

            @endforelse

        </div>

    </div>

    </div>

</div>


