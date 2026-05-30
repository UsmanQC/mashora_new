<?php

use App\Models\VerifyPhoneNumber;
use App\Services\SmsService;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

new #[Layout('layouts::doctor-guest')] #[Title('Verify mobile number')] class extends Component
{
    public string $phone = '';

    public string $code = '';

    public ?string $devOtpDisplay = null;

    public function mount(): void
    {
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
        // TESTING: static OTP. Restore `sprintf('%04d', random_int(0, 9999))` and uncomment the SMS send below before going live.
        $code = '1111';

        VerifyPhoneNumber::query()
            ->where('phone', $this->phone)
            ->where('user_type', 'doctor')
            ->delete();

        $message = __('doctor.auth.verification_sms', ['code' => $code]);
        $sms = app(SmsService::class);
        // $sms->send($message, $this->phone, $code);

        VerifyPhoneNumber::query()->create([
            'phone' => $this->phone,
            'verification_code' => $code,
            'user_type' => 'doctor',
        ]);

        $this->devOtpDisplay = $sms->isLive() ? null : $code;
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

<div class="space-y-8">
    <div class="space-y-2 text-center sm:text-start">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.otp_heading') }}</flux:heading>
        <flux:text class="text-sm text-zinc-600">{{ __('doctor.auth.otp_lead') }}</flux:text>
        <flux:text class="font-medium tabular-nums text-zinc-800" dir="ltr">+{{ $phone }}</flux:text>
    </div>

    @if (filled($devOtpDisplay))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950" role="status">
            {{ __('doctor.auth.otp_dev_banner', ['code' => $devOtpDisplay]) }}
        </div>
    @endif

    <form wire:submit="verifyOtp" class="space-y-4">
        <flux:field>
            <flux:label>{{ __('doctor.auth.otp_label') }}</flux:label>
            <flux:input
                wire:model="code"
                type="text"
                inputmode="numeric"
                maxlength="4"
                autocomplete="one-time-code"
                class="text-center font-mono text-lg tracking-[0.35em]"
            />
            <flux:error name="code" />
        </flux:field>

        <flux:button class="w-full bg-[#132A6E]! text-white! hover:brightness-95!" type="submit" variant="primary">
            {{ __('doctor.auth.otp_verify') }}
        </flux:button>

        <flux:button type="button" variant="ghost" class="w-full" wire:click="sendOtp">
            {{ __('doctor.auth.otp_resend') }}
        </flux:button>
    </form>
</div>
