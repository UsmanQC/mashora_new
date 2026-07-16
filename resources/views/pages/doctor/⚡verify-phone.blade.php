<?php

use App\Livewire\Concerns\RedirectsAuthenticatedDoctorsFromGuestPages;
use App\Models\VerifyPhoneNumber;
use App\Services\SmsService;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

new #[Layout('layouts::doctor-guest')] #[Title('Verify mobile number')] class extends Component
{
    use RedirectsAuthenticatedDoctorsFromGuestPages;

    public string $phone = '';

    public string $code = '';

    public ?string $devOtpDisplay = null;

    public ?string $smsError = null;

    public function mount(): void
    {
        if ($this->redirectAuthenticatedDoctorAwayFromGuestPages()) {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) request()->query('phone', '')) ?? '';

        if ($digits === '') {
            throw new HttpException(403);
        }

        $this->phone = $digits;

        $recent = VerifyPhoneNumber::query()
            ->where('phone', $digits)
            ->where('user_type', 'doctor')
            ->where('created_at', '>', now()->subMinute())
            ->exists();

        if (! $recent) {
            $this->sendOtp();
        } elseif (! app(SmsService::class)->isLive()) {
            $this->devOtpDisplay = VerifyPhoneNumber::query()
                ->where('phone', $digits)
                ->where('user_type', 'doctor')
                ->value('verification_code');
        }
    }

    public function sendOtp(): void
    {
        $this->smsError = null;
        $this->resetErrorBag('code');

        try {
            $code = sprintf('%04d', random_int(0, 9999));

            VerifyPhoneNumber::query()
                ->where('phone', $this->phone)
                ->where('user_type', 'doctor')
                ->delete();

            VerifyPhoneNumber::query()->create([
                'phone' => $this->phone,
                'verification_code' => $code,
                'user_type' => 'doctor',
            ]);

            $message = __('doctor.auth.verification_sms', ['code' => $code]);
            $sms = app(SmsService::class);
            $result = $sms->send($message, $this->phone, $code);
            $ok = is_array($result) ? (bool) ($result['ok'] ?? false) : (bool) $result;

            if ($sms->isLive() && ! $ok) {
                $this->devOtpDisplay = null;
                $this->smsError = is_array($result)
                    ? (string) ($result['error'] ?? __('doctor.auth.otp_send_failed'))
                    : __('doctor.auth.otp_send_failed');

                return;
            }

            $this->devOtpDisplay = $sms->isLive() ? null : $code;
        } catch (\Throwable $e) {
            report($e);
            $this->devOtpDisplay = null;
            $this->smsError = __('doctor.auth.otp_send_failed');
        }
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:4'],
        ], [], ['code' => __('doctor.auth.otp_label')]);

        $record = VerifyPhoneNumber::query()
            ->where('phone', $this->phone)
            ->where('user_type', 'doctor')
            ->first();

        if ($record === null || $record->verification_code !== $this->code) {
            $this->addError('code', __('doctor.auth.otp_invalid'));

            return;
        }

        $record->delete();

        session(['doctor_otp_verified_phone' => $this->phone]);

        if (config('doctor.registration_invite_only')) {
            $this->redirect(URL::temporarySignedRoute(
                'doctor.register',
                now()->addHours(2),
                ['phone' => $this->phone],
            ), navigate: false);

            return;
        }

        $this->redirect(route('doctor.register', ['phone' => $this->phone]), navigate: false);
    }
}; ?>

<div class="flex min-h-0 w-full flex-col text-start">
    <div class="mb-2 sm:mb-3">
        <flux:button
            :href="route('doctor.welcome')"
            wire:navigate
            variant="ghost"
            size="sm"
            icon="arrow-left"
            aria-label="{{ __('pagination.previous') }}"
            title="{{ __('pagination.previous') }}"
            class="px-0 text-zinc-600 hover:text-zinc-900"
        />
    </div>

    <flux:heading size="lg" class="patient-auth-heading !text-zinc-900 text-balance sm:!text-2xl">{{ __('doctor.auth.otp_heading') }}</flux:heading>
    <flux:text class="mt-1 max-w-sm text-sm text-balance text-zinc-600 sm:mt-2 sm:text-base">{{ __('doctor.auth.otp_lead') }}</flux:text>
    <flux:text class="mt-2 text-sm font-medium tabular-nums text-zinc-800 sm:mt-3 sm:text-base" dir="ltr">+{{ $phone }}</flux:text>

    @if (filled($devOtpDisplay))
        <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950 sm:mt-4" role="status">
            {{ __('doctor.auth.otp_dev_banner', ['code' => $devOtpDisplay]) }}
        </div>
    @endif

    @if (filled($smsError))
        <div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-900 sm:mt-4" role="alert">
            {{ $smsError }}
        </div>
    @endif

    <form wire:submit="verifyOtp" class="mt-5 space-y-4 sm:mt-8 sm:space-y-5">
        <flux:field class="patient-auth-otp">
            <flux:label class="!text-zinc-900">{{ __('doctor.auth.otp_label') }}</flux:label>
            <flux:otp
                wire:model="code"
                :length="4"
                label:sr-only
                error:class="text-center"
                class="patient-auth-otp-group"
                input:class="patient-auth-otp-input"
            />
            <flux:error name="code" />
        </flux:field>

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
            {{ __('doctor.auth.otp_verify') }}
        </flux:button>

        <div class="flex flex-col items-center gap-2 text-center text-sm text-zinc-600">
            <flux:button
                type="button"
                variant="ghost"
                size="sm"
                wire:click="sendOtp"
                class="!text-zinc-600 hover:!text-zinc-900"
            >
                {{ __('doctor.auth.otp_resend') }}
            </flux:button>
        </div>
    </form>
</div>
