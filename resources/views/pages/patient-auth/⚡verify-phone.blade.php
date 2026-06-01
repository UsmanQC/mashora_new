<?php

use App\Models\VerifyPhoneNumber;
use App\Services\SmsService;
use App\Support\PatientPhone;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

new #[Layout('layouts::patient-auth')] #[Title('Verify mobile number')] class extends Component
{
    public string $phone = '';

    public string $code = '';

    public ?string $devOtpDisplay = null;

    public function mount(): void
    {
        $digits = PatientPhone::normalize((string) request()->query('phone', ''));

        if ($digits === '') {
            throw new HttpException(403);
        }

        $this->phone = $digits;

        $recent = VerifyPhoneNumber::query()
            ->where('phone', $digits)
            ->where('user_type', 'patient')
            ->where('created_at', '>', now()->subMinute())
            ->exists();

        if (! $recent) {
            $this->sendOtp();
        } elseif (! app(SmsService::class)->isLive()) {
            $this->devOtpDisplay = VerifyPhoneNumber::query()
                ->where('phone', $digits)
                ->where('user_type', 'patient')
                ->value('verification_code');
        }
    }

    public function sendOtp(): void
    {
        // TESTING: static OTP. Restore `sprintf('%04d', random_int(0, 9999))` and uncomment the SMS send below before going live.
        $code = '1111';

        VerifyPhoneNumber::query()
            ->where('phone', $this->phone)
            ->where('user_type', 'patient')
            ->delete();

        $message = __('patient_auth.verification_sms', ['code' => $code]);
        $sms = app(SmsService::class);
        // $sms->send($message, $this->phone, $code);

        VerifyPhoneNumber::query()->create([
            'phone' => $this->phone,
            'verification_code' => $code,
            'user_type' => 'patient',
        ]);

        $this->devOtpDisplay = $sms->isLive() ? null : $code;
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:4'],
        ], [], ['code' => __('patient_auth.otp_label')]);

        $record = VerifyPhoneNumber::query()
            ->where('phone', $this->phone)
            ->where('user_type', 'patient')
            ->first();

        if ($record === null || $record->verification_code !== $this->code) {
            $this->addError('code', __('patient_auth.otp_invalid'));

            return;
        }

        $record->delete();

        session(['patient_otp_verified_phone' => $this->phone]);

        $this->redirect(URL::temporarySignedRoute(
            'patient.auth.sign-up',
            now()->addHour(),
            ['phone' => $this->phone],
        ), navigate: false);
    }
}; ?>

<div>
    <div class="mb-4 text-start sm:mb-6">
        <flux:button
            :href="route('patient.phone', ['phone' => $phone])"
            wire:navigate
            variant="ghost"
            size="sm"
            icon="arrow-left"
            aria-label="{{ __('pagination.previous') }}"
            title="{{ __('pagination.previous') }}"
            class="px-0 text-zinc-600 hover:text-zinc-900"
        />
    </div>

    <flux:heading size="xl" class="patient-auth-heading text-balance">{{ __('patient_auth.otp_heading') }}</flux:heading>
    <flux:text class="mx-auto mt-2 max-w-sm text-balance text-zinc-600">{{ __('patient_auth.otp_lead') }}</flux:text>
    <flux:text class="mt-3 font-medium tabular-nums text-zinc-800" dir="ltr">+{{ $phone }}</flux:text>

    @if (filled($devOtpDisplay))
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950" role="status">
            {{ __('patient_auth.otp_dev_banner', ['code' => $devOtpDisplay]) }}
        </div>
    @endif

    <form wire:submit="verifyOtp" class="mt-8 space-y-5">
        <flux:otp
            wire:model="code"
            :length="4"
            :label="__('patient_auth.otp_label')"
            label:sr-only
            error:class="text-center"
            class="mx-auto"
        />

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
            {{ __('patient_auth.otp_verify') }}
        </flux:button>

        <div class="flex flex-col items-center gap-2 text-center text-sm text-zinc-600">
            <flux:button type="button" variant="ghost" size="sm" wire:click="sendOtp">
                {{ __('patient_auth.otp_resend') }}
            </flux:button>
        </div>
    </form>
</div>
