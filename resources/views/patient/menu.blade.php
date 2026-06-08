<x-layouts::patient>
    <div class="mx-auto max-w-5xl pb-28 sm:pb-14">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <flux:heading size="xl" class="sr-only">{{ __('patient.nav.menu') }}</flux:heading>

            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-5"
                aria-label="{{ __('patient.menu.grid_aria') }}"
            >
                <a
                    href="{{ route('patient.notifications') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="bell" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.notifications') }}</span>
                </a>

                <a
                    href="{{ route('patient.wallet') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="banknotes" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.wallet') }}</span>
                </a>

                <a
                    href="{{ route('patient.medications') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="clipboard-document" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.medications') }}</span>
                </a>

                <a
                    href="{{ route('profile.edit') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="lock-closed" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.password') }}</span>
                </a>

                <a
                    href="{{ route('profile.edit') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="user-circle" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.personal_profile') }}</span>
                </a>

                <a
                    href="{{ route('patient.phone') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="phone" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.phone_number') }}</span>
                </a>

                <a
                    href="{{ route('patient.favorites') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="heart" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.favorites') }}</span>
                </a>

                <a
                    href="{{ route('patient.support') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="question-mark-circle" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.support') }}</span>
                </a>

                <div
                    class="flex flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white sm:p-8"
                    role="group"
                    aria-label="{{ __('patient.menu.language_aria') }}"
                >
                    <flux:icon name="language" variant="outline" class="mx-auto size-12 text-zinc-500" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 sm:text-sm">{{ __('patient.menu.language') }}</span>
                    <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                        <a
                            href="{{ route('patient.locale', ['locale' => 'en']) }}"
                            wire:navigate="false"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition hover:opacity-90 {{ app()->getLocale() === 'en' ? 'bg-mashora-brand text-white shadow-sm' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' }}"
                        >
                            {{ __('patient.menu.locale_en') }}
                        </a>
                        <a
                            href="{{ route('patient.locale', ['locale' => 'ar']) }}"
                            wire:navigate="false"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition hover:opacity-90 {{ app()->getLocale() === 'ar' ? 'bg-mashora-brand text-white shadow-sm' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' }}"
                        >
                            {{ __('patient.menu.locale_ar_short') }}
                        </a>
                    </div>
                </div>

                <a
                    href="{{ route('patient.privacy') }}"
                    wire:navigate
                    class="group flex cursor-pointer flex-col gap-5 rounded-2xl border border-zinc-200/90 bg-white p-6 text-center shadow-[0_1px_4px_-1px_rgba(15,23,42,0.08)] outline-none ring-offset-2 ring-offset-white transition hover:border-[#3c5cf7]/35 hover:shadow-[0_4px_20px_-10px_rgba(60,92,247,0.35)] focus-visible:ring-2 focus-visible:ring-[#3c5cf7]/35 sm:p-8"
                >
                    <flux:icon name="shield-check" variant="outline" class="mx-auto size-12 transition text-zinc-500 group-hover:text-[#3c5cf7]" />
                    <span class="block text-[0.8rem] font-semibold leading-snug text-zinc-900 group-hover:text-[#3c5cf7] sm:text-sm">{{ __('patient.menu.privacy') }}</span>
                </a>
            </div>

            @auth
                
            @endauth
        </div>
    </div>
</x-layouts::patient>
