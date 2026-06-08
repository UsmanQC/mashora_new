<?php

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Menu')] class extends Component
{
    public function doctor(): ?Doctor
    {
        $doctor = Auth::guard('doctor')->user();

        return $doctor instanceof Doctor ? $doctor : null;
    }

    /**
     * @return array<int, array{label: string, icon: string, route: string|null, available: bool}>
     */
    public function menuItems(): array
    {
        return [
            ['label' => __('Personal account'), 'icon' => 'user-circle', 'route' => 'doctor.settings.profile', 'available' => Route::has('doctor.settings.profile')],
            ['label' => __('Notifications'), 'icon' => 'bell', 'route' => 'doctor.settings.notifications', 'available' => Route::has('doctor.settings.notifications')],
            ['label' => __('Bank account'), 'icon' => 'credit-card', 'route' => 'doctor.settings.bank-account', 'available' => Route::has('doctor.settings.bank-account')],
            ['label' => __('Wallet'), 'icon' => 'banknotes', 'route' => 'doctor.settings.wallet', 'available' => Route::has('doctor.settings.wallet')],
            ['label' => __('Support'), 'icon' => 'lifebuoy', 'route' => 'doctor.settings.support', 'available' => Route::has('doctor.settings.support')],
            ['label' => __('Privacy policy'), 'icon' => 'shield-check', 'route' => 'doctor.settings.privacy-policy', 'available' => Route::has('doctor.settings.privacy-policy')],
            ['label' => __('Invoices'), 'icon' => 'document-text', 'route' => 'doctor.settings.invoices', 'available' => Route::has('doctor.settings.invoices')],
            ['label' => __('Working hours'), 'icon' => 'clock', 'route' => 'doctor.settings.working-hours', 'available' => Route::has('doctor.settings.working-hours')],
            ['label' => __('Duration and price'), 'icon' => 'currency-dollar', 'route' => 'doctor.settings.duration', 'available' => Route::has('doctor.settings.duration')],
            ['label' => __('Ratings'), 'icon' => 'star', 'route' => 'doctor.ratings', 'available' => Route::has('doctor.ratings')],
            ['label' => __('Appointments'), 'icon' => 'calendar-days', 'route' => 'doctor.appointments', 'available' => Route::has('doctor.appointments')],
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.settings.title') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600">{{ __('doctor.settings.subtitle') }}</flux:text>
    </div>

    @if ($doc = $this->doctor())
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <flux:avatar
                    :name="$doc->displayName()"
                    :src="$doc->profilePhotoUrl()"
                    circle
                    size="lg"
                />
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold text-zinc-900">{{ $doc->displayName() }}</p>
                    <p class="text-sm text-zinc-500">{{ __('Manage your account preferences') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->menuItems() as $item)
            @php($isActive = $item['available'] && $item['route'] && request()->routeIs($item['route']))
            @if ($item['available'] && $item['route'])
                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    class="group rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#3C5CF7]/35 hover:shadow-md"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl {{ $isActive ? 'bg-[#132A6E] text-white' : 'bg-zinc-100 text-zinc-600 group-hover:bg-[#3C5CF7]/10 group-hover:text-[#3C5CF7]' }}">
                                <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                            </span>
                            <span class="text-sm font-semibold text-zinc-800">{{ $item['label'] }}</span>
                        </div>
                        <flux:icon name="chevron-right" variant="mini" class="size-4 text-zinc-400 rtl:rotate-180" />
                    </div>
                </a>
            @endif
        @endforeach
    </div>

</div>
