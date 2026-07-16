@php
    $pwaApp = $pwaApp ?? 'patient';
    $isAr = app()->getLocale() === 'ar';
    $appConfig = config("pwa.apps.{$pwaApp}", config('pwa.apps.patient'));
    $showPush = (bool) ($showPush ?? false);
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
        <div class="flex h-12 shrink-0 items-center justify-start">
            @include('partials.patient-brand-logo', [
                'svgClass' => 'h-9 w-auto max-w-[5.5rem] object-contain object-start',
            ])
        </div>
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
                @if ($showPush)
                    <button
                        type="button"
                        id="awaan-pwa-enable-push"
                        class="rounded-xl bg-zinc-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-zinc-800"
                    >
                        {{ $isAr ? 'السماح بالإشعارات' : 'Allow notifications' }}
                    </button>
                @endif
                <button
                    type="button"
                    id="awaan-pwa-install-dismiss"
                    class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50"
                >
                    {{ $isAr ? 'لاحقاً' : 'Later' }}
                </button>
            </div>
            <p id="awaan-pwa-enable-push-hint" class="mt-2 hidden text-[11px] text-amber-700"></p>
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
        const pushBtn = document.getElementById('awaan-pwa-enable-push');
        const pushHint = document.getElementById('awaan-pwa-enable-push-hint');
        const isAr = @json($isAr);

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

        pushBtn?.addEventListener('click', async () => {
            pushBtn.disabled = true;
            if (pushHint) {
                pushHint.classList.add('hidden');
            }

            try {
                const started = Date.now();
                while (typeof window.enableAwaanPushNotifications !== 'function' && Date.now() - started < 5000) {
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }

                if (typeof window.enableAwaanPushNotifications !== 'function') {
                    if (pushHint) {
                        pushHint.textContent = isAr
                            ? 'ارفع public/js/awaan-fcm.js على السيرفر ثم حدّث الصفحة.'
                            : 'Upload public/js/awaan-fcm.js to the server, then refresh.';
                        pushHint.classList.remove('hidden');
                    }
                    return;
                }

                const ok = await window.enableAwaanPushNotifications();
                if (ok) {
                    pushBtn.textContent = isAr ? 'تم التفعيل' : 'Enabled';
                    return;
                }

                if (pushHint) {
                    pushHint.textContent = isAr
                        ? 'لم تُقبل الإشعارات. تحقق من إعدادات الموقع.'
                        : 'Notifications were not allowed. Check site settings.';
                    pushHint.classList.remove('hidden');
                }
            } finally {
                pushBtn.disabled = false;
            }
        });
    })();
</script>
