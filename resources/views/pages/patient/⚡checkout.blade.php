<?php

use App\Models\Doctor;
use App\Models\TemporaryAppointment;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;

new #[Layout('layouts::patient')] #[Title('Payment')] class extends Component
{
    public TemporaryAppointment $temporaryAppointment;

    public string $paymentError = '';

    public function mount(TemporaryAppointment $temporaryAppointment): void
    {
        if ($temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        $this->temporaryAppointment = $temporaryAppointment->load('doctor');
    }

    public function formattedDate(): string
    {
        try {
            return Carbon::parse($this->temporaryAppointment->appointment_date)
                ->locale(app()->getLocale())
                ->format(__('patient_booking.date_format'));
        } catch (\Throwable) {
            return (string) $this->temporaryAppointment->appointment_date;
        }
    }

    public function formattedTime(): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', (string) $this->temporaryAppointment->start_time)
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return (string) $this->temporaryAppointment->start_time;
        }
    }

    public function specialistName(): string
    {
        $doctor = $this->temporaryAppointment->doctor;
        if ($doctor instanceof Doctor) {
            return $doctor->displayName();
        }

        return '';
    }

    public function startMyFatoorahPayment(): void
    {
        if ($this->temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        if (empty(config('myfatoorah.api_key'))) {
            $this->paymentError = __('patient_booking.payment_api_missing');

            return;
        }

        try {
            $mfConfig = [
                'apiKey' => config('myfatoorah.api_key'),
                'isTest' => (bool) config('myfatoorah.is_test'),
                'vcCode' => (string) config('myfatoorah.vc_code'),
            ];

            $temp = $this->temporaryAppointment->fresh();
            if ($temp === null) {
                abort(404);
            }

            $mfObj = new MyFatoorahPayment($mfConfig);

            $paymentData = [
                'NotificationOption' => 'LNK',
                'CustomerName' => (string) $temp->patient_name,
                'InvoiceValue' => (float) $temp->total,
                'DisplayCurrencyIso' => 'SAR',
                'CallBackUrl' => route('patient.payment.success', ['temporaryAppointment' => $temp->id]),
                'ErrorUrl' => route('patient.payment.failed', ['temporaryAppointment' => $temp->id]),
                'CustomerReference' => $temp->id,
                'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ];

            $data = $mfObj->sendPayment($paymentData);

            $invoiceUrl = $data->InvoiceURL ?? null;
            $invoiceId = $data->InvoiceId ?? null;

            $temp->payment_invoice_id = $invoiceId !== null ? (string) $invoiceId : null;
            $temp->payment_invoice_url = $invoiceUrl !== null ? (string) $invoiceUrl : null;
            $temp->save();

            if (is_string($invoiceUrl) && $invoiceUrl !== '') {
                $this->redirect($invoiceUrl);

                return;
            }

            $this->paymentError = __('patient_booking.payment_invoice_failed');
        } catch (\Throwable $e) {
            report($e);
            $this->paymentError = __('patient_booking.payment_start_failed');
        }
    }
}; ?>

<div class="mx-auto max-w-4xl space-y-8 px-4 py-6 pb-28 sm:pb-12">
    @if (session('flash_payment'))
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('flash_payment') }}</p>
    @endif
    <header class="space-y-2">
        <flux:heading size="xl" class="font-semibold text-zinc-900">
            {{ __('patient_booking.checkout_title') }}
        </flux:heading>
        <flux:text class="text-zinc-600">{{ __('patient_booking.checkout_subtitle') }}</flux:text>
    </header>

    <div class="grid gap-8 lg:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-md shadow-black/10">
            <div class="flex justify-between gap-3 border-b border-zinc-100 pb-3">
                <span class="text-sm font-semibold text-zinc-600">{{ __('patient_booking.specialist_name') }}</span>
                <span class="min-w-0 text-end text-sm font-semibold text-zinc-900">{{ $this->specialistName() }}</span>
            </div>
            <div class="mt-3 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_date') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->formattedDate() }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_time') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->formattedTime() }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_duration') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->temporaryAppointment->duration }}</span>
            </div>
            <hr class="my-4 border-zinc-100" />
            <div class="flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_price') }}</span>
                <span class="font-medium tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->amount, 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.discount') }}</span>
                <span class="font-medium tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->discount, 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
            <div class="mt-3 flex justify-between gap-3 border-t border-zinc-100 pt-3 text-sm font-semibold">
                <span class="text-zinc-800">{{ __('patient_booking.total') }}</span>
                <span class="tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->total, 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
        </div>

        <div class="space-y-4">
            @if ($paymentError !== '')
                <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $paymentError }}</p>
            @endif

            @if (filled(config('myfatoorah.api_key')))
                <flux:text class="text-sm text-zinc-600">{{ __('patient_booking.payment_secure_note') }}</flux:text>
                <flux:button
                    type="button"
                    variant="primary"
                    class="w-full border-[#0B163E] !bg-[#0B163E] !text-white hover:!brightness-[0.97] sm:w-auto"
                    wire:click="startMyFatoorahPayment"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="startMyFatoorahPayment">{{ __('patient_booking.pay_now') }}</span>
                    <span wire:loading wire:target="startMyFatoorahPayment">{{ __('patient_booking.payment_processing') }}</span>
                </flux:button>
            @else
                <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('patient_booking.payment_api_missing') }}</p>
            @endif

            <div class="pt-2">
                <flux:link :href="route('patient.home')" wire:navigate>{{ __('patient_booking.back_home') }}</flux:link>
            </div>
        </div>
    </div>

    @include('partials.patient-checkout-payment-methods')
</div>
