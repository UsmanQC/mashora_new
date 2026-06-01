<?php

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Account status')] class extends Component
{
    public function mount(): void
    {
        $doctor = $this->doctor();

        if ($doctor instanceof Doctor && $doctor->status === 'approved') {
            $this->redirect(route('doctor.dashboard'), navigate: true);
        }
    }

    public function doctor(): ?Doctor
    {
        $doctor = Auth::guard('doctor')->user();

        return $doctor instanceof Doctor ? $doctor : null;
    }
}; ?>

<div class="mx-auto flex min-h-[70vh] max-w-xl items-center justify-center px-4 py-10">
    @php($doc = $this->doctor())
    @php($isRejected = $doc && $doc->status === 'rejected')

    <div class="w-full overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="h-1.5 w-full {{ $isRejected ? 'bg-red-500' : 'bg-linear-to-r from-[#1565c0] to-[#42a5f5]' }}"></div>

        <div class="px-6 py-10 text-center sm:px-10">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full {{ $isRejected ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-[#1565c0]' }}">
                <flux:icon
                    name="{{ $isRejected ? 'x-circle' : 'clock' }}"
                    variant="outline"
                    class="size-8"
                />
            </div>

            <span class="mt-5 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $isRejected ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">
                <span class="size-1.5 rounded-full {{ $isRejected ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                {{ $isRejected ? __('doctor.account_status.rejected_badge') : __('doctor.account_status.pending_badge') }}
            </span>

            <flux:heading size="lg" class="mt-4 font-semibold text-zinc-900">
                {{ $isRejected ? __('doctor.account_status.rejected_title') : __('doctor.account_status.pending_title') }}
            </flux:heading>

            <flux:text class="mx-auto mt-3 max-w-md text-zinc-600">
                {{ $isRejected ? __('doctor.account_status.rejected_body') : __('doctor.account_status.pending_body') }}
            </flux:text>

            @unless ($isRejected)
                <div class="mx-auto mt-6 flex max-w-md items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/60 px-4 py-3 text-start">
                    <flux:icon name="envelope" variant="outline" class="mt-0.5 size-5 shrink-0 text-[#1565c0]" />
                    <flux:text class="text-sm text-zinc-600">
                        {{ __('doctor.account_status.email_note') }}
                    </flux:text>
                </div>
            @endunless

            <flux:text class="mx-auto mt-6 max-w-md text-sm text-zinc-500">
                {!! __('doctor.account_status.help_html') !!}
            </flux:text>

            <form method="POST" action="{{ route('doctor.logout') }}" class="mt-8">
                @csrf
                <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle">
                    {{ __('doctor.auth.sign_out') }}
                </flux:button>
            </form>
        </div>
    </div>
</div>
