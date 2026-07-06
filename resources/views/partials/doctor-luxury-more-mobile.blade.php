@php
    /** @var \App\Models\Doctor $doc */
    $doc = $this->currentDoctor;
@endphp

<div
    class="doctor-luxury-more relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-more"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            {{ __('doctor.menu.title') }}
        </h1>
    </header>

    <main class="mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col space-y-5 overflow-y-auto overscroll-contain px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))]">
        <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            <div class="flex items-start gap-3">
                <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#10B981]/10 text-base font-bold text-[#047857]">
                    @if ($doc->profilePhotoUrl())
                        <img src="{{ $doc->profilePhotoUrl() }}" alt="" class="size-full object-cover" />
                    @else
                        {{ $this->initialsFor($doc->displayName()) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-bold text-slate-900">{{ $doc->displayName() }}</p>
                    @if ($this->profileSubtitle !== '')
                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $this->profileSubtitle }}</p>
                    @endif
                    @if ($doc->status === 'approved')
                        <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-[#047857]">
                            <flux:icon name="check-badge" variant="solid" class="size-4 shrink-0" />
                            <span>
                                {{ __('doctor.mobile.more_verified') }}
                                @if (filled($doc->registration_number))
                                    · {{ __('doctor.mobile.more_license', ['number' => $doc->registration_number]) }}
                                @endif
                            </span>
                        </p>
                    @elseif ($doc->status === 'pending')
                        <p class="mt-2 text-xs font-medium text-amber-700">{{ __('doctor.account_status.pending_badge') }}</p>
                    @endif
                </div>
            </div>
        </section>

        @foreach ($this->menuSections as $section)
            <section>
                <h2 class="mb-2 px-0.5 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ $section['heading'] }}
                </h2>
                <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                    @foreach ($section['items'] as $item)
                        @if ($item['available'])
                            @php
                                $href = route($item['route'], $item['query'] ?? []);
                                $badge = $this->badgeValue($item['badge_key'] ?? null);
                            @endphp
                            <a
                                href="{{ $href }}"
                                wire:navigate
                                wire:key="doctor-more-{{ $item['route'] }}-{{ $item['label'] }}"
                                @class([
                                    'flex items-center gap-3 px-4 py-3.5 transition hover:bg-slate-50/80',
                                    'border-b border-slate-100' => ! $loop->last,
                                ])
                            >
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#047857]">
                                    <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                                    <p class="truncate text-[0.6875rem] text-slate-500">{{ $item['sub'] }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    @if ($badge)
                                        <span class="text-xs font-semibold tabular-nums text-slate-500">{{ $badge }}</span>
                                    @endif
                                    <flux:icon name="chevron-right" variant="mini" class="size-4 text-slate-300 rtl:rotate-180" />
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach

        <form method="POST" action="{{ route('doctor.logout') }}" class="pb-2">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-100 bg-white px-4 py-3.5 text-sm font-semibold text-rose-600 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition hover:bg-rose-50"
            >
                <flux:icon name="arrow-right-start-on-rectangle" variant="outline" class="size-5" />
                {{ __('doctor.auth.sign_out') }}
            </button>
        </form>
    </main>
</div>
