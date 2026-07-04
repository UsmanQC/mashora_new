<x-layouts::patient>
@php
    $user = auth()->user();
    $profilePhotoUrl = $user !== null && filled($user->profile_photo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $user->profile_photo_path)
        : null;
    $backUrl = auth()->check() ? route('patient.menu') : route('patient.home');
    $backLabel = auth()->check() ? __('patient.nav.menu') : __('patient.nav.home');
@endphp

<div class="patient-luxury-important-numbers bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-important-numbers">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.nav.important_numbers'),
            'subtitle' => __('patient.numbers_intro'),
            'profilePhotoUrl' => $profilePhotoUrl,
            'userName' => $user?->name,
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
            'testId' => 'patient-important-numbers-header',
        ])
    </div>

    <div class="mx-auto max-w-5xl space-y-5 px-6 pt-5 sm:space-y-6 sm:px-6 sm:py-6 sm:pb-10">
        <div class="hidden sm:block">
            <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('patient.nav.important_numbers') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-600">{{ __('patient.numbers_intro') }}</flux:text>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-[#10B981]/25 sm:p-6 sm:shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)]">
            @include('partials.patient-important-numbers-board')
        </div>
    </div>
</div>
</x-layouts::patient>
