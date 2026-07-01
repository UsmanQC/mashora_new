@php
    $pwaApp = $pwaApp ?? 'patient';
    $isAr = app()->getLocale() === 'ar';
    $appConfig = config("pwa.apps.{$pwaApp}", config('pwa.apps.patient'));
@endphp

<div
    id="awaan-pwa-install"
    class="fixed inset-x-4 bottom-20 z-[70] hidden max-w-md rounded-2xl border border-emerald-200 bg-white p-4 shadow-xl sm:inset-x-auto sm:start-4 sm:bottom-6"
    dir="{{ $isAr ? 'rtl' : 'ltr' }}"
    role="dialog"
    aria-live="polite"
    aria-label="{{ $isAr ? 'تثبيت التطبيق' : 'Install app' }}"
>
    <div class="flex items-start gap-3">
        <img
            src="{{ asset('images/pwa/icon-192.png') }}"
            alt=""
            class="h-12 w-12 shrink-0 rounded-xl"
            width="48"
            height="48"
        />
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-zinc-900">
                {{ $isAr ? 'ثبّت تطبيق '.$appConfig['short_name'] : 'Install '.$appConfig['name'] }}
            </p>
            <p class="mt-1 text-xs leading-relaxed text-zinc-600">
                {{ $isAr ? 'أضف أوان إلى الشاشة الرئيسية للوصول السريع.' : 'Add Awaan to your home screen for quick access.' }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    id="awaan-pwa-install-confirm"
                    class="rounded-xl bg-[#10B981] px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-600"
                >
                    {{ $isAr ? 'تثبيت' : 'Install' }}
                </button>
                <button
                    type="button"
                    id="awaan-pwa-install-dismiss"
                    class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50"
                >
                    {{ $isAr ? 'لاحقاً' : 'Later' }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const root = document.getElementById('awaan-pwa-install');
        if (!root || window.matchMedia('(display-mode: standalone)').matches) {
            return;
        }

        const storageKey = 'awaan-pwa-install-dismissed-{{ $pwaApp }}';
        if (localStorage.getItem(storageKey) === '1') {
            return;
        }

        let deferredPrompt = null;
        const confirmBtn = document.getElementById('awaan-pwa-install-confirm');
        const dismissBtn = document.getElementById('awaan-pwa-install-dismiss');

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredPrompt = event;
            root.classList.remove('hidden');
        });

        confirmBtn?.addEventListener('click', async () => {
            if (!deferredPrompt) {
                return;
            }

            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            root.classList.add('hidden');
        });

        dismissBtn?.addEventListener('click', () => {
            localStorage.setItem(storageKey, '1');
            root.classList.add('hidden');
        });
    })();
</script>
