<div
    x-show="doctorMenuOpen"
    x-cloak
    class="fixed inset-0 z-[60] lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="doctor-mobile-menu-title"
>
    <div
        class="absolute inset-0 bg-zinc-900/45 backdrop-blur-[1px]"
        x-show="doctorMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="doctorMenuOpen = false"
    ></div>

    <aside
        id="doctor-mobile-menu-drawer"
        class="portal-chrome-sidebar absolute inset-y-0 start-0 flex w-[min(100%,18.5rem)] min-h-0 flex-col overflow-hidden bg-[#10B981] text-white shadow-2xl"
        x-show="doctorMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full rtl:translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full rtl:translate-x-full"
        @click.stop
    >
        <div class="flex shrink-0 items-center justify-between gap-2 border-b border-white/10 px-4 pb-4 pt-4">
            <div class="min-w-0">
                <p id="doctor-mobile-menu-title" class="text-sm font-semibold text-white">{{ __('doctor.menu.title') }}</p>
                <p class="mt-0.5 text-xs text-white/70">{{ __('doctor.menu.subtitle') }}</p>
            </div>
            <button
                type="button"
                data-test="doctor-mobile-menu-close"
                class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/15"
                :aria-label="@js(__('doctor.menu.close'))"
                @click="doctorMenuOpen = false"
            >
                <flux:icon name="x-mark" variant="mini" class="size-5" />
            </button>
        </div>

        <div class="shrink-0 border-b border-white/10 px-4 py-3">
            @include('partials.doctor-brand-strip', ['density' => 'compact', 'align' => 'start'])
        </div>

        <nav
            class="portal-sidebar-scroll min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain px-2 py-4"
            aria-label="{{ __('doctor.mobile_nav_label') }}"
            @click="doctorMenuOpen = false"
        >
            @include('partials.doctor-sidebar-nav')
        </nav>

        <div class="shrink-0 space-y-3 border-t border-white/10 px-4 py-4 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            @include('partials.doctor-language-switch', ['variant' => 'header', 'showLabel' => true])
        </div>
    </aside>
</div>
