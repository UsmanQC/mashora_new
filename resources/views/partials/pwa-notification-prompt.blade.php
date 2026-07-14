{{-- Shown when push is configured but the user has not enabled notifications yet. --}}
@php
    $isAr = app()->getLocale() === 'ar';
@endphp

<div
    id="awaan-push-enable"
    class="fixed inset-x-4 bottom-20 z-[75] hidden max-w-md rounded-2xl border border-emerald-200 bg-white p-4 shadow-xl sm:inset-x-auto sm:start-4 sm:bottom-6"
    dir="{{ $isAr ? 'rtl' : 'ltr' }}"
    role="dialog"
    aria-live="polite"
>
    <div class="flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.25 9a6.75 6.75 0 0 1 13.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 0 1-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 1 1-7.48 0 24.18 24.18 0 0 1-4.83-1.244.75.75 0 0 1-.298-1.205A8.217 8.217 0 0 0 5.25 9.75V9Zm4.502 8.9a2.25 2.25 0 1 0 4.496 0 25.057 25.057 0 0 1-4.496 0Z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-zinc-900">
                {{ $isAr ? 'تفعيل الإشعارات' : 'Enable notifications' }}
            </p>
            <p class="mt-1 text-xs leading-relaxed text-zinc-600">
                {{ $isAr
                    ? 'اضغط للسماح بالإشعارات حتى يصلك تنبيه بالمواعيد والرسائل.'
                    : 'Tap to allow notifications for appointments and messages.' }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    id="awaan-push-enable-btn"
                    class="rounded-xl bg-[#10B981] px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-600"
                >
                    {{ $isAr ? 'السماح بالإشعارات' : 'Allow notifications' }}
                </button>
                <button
                    type="button"
                    id="awaan-push-enable-dismiss"
                    class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50"
                >
                    {{ $isAr ? 'لاحقاً' : 'Later' }}
                </button>
            </div>
            <p id="awaan-push-enable-hint" class="mt-2 hidden text-[11px] text-amber-700"></p>
        </div>
    </div>
</div>

<script>
    (() => {
        const root = document.getElementById('awaan-push-enable');
        const enableBtn = document.getElementById('awaan-push-enable-btn');
        const dismissBtn = document.getElementById('awaan-push-enable-dismiss');
        const hint = document.getElementById('awaan-push-enable-hint');
        const isAr = document.documentElement.lang?.startsWith('ar');

        dismissBtn?.addEventListener('click', () => {
            root?.classList.add('hidden');
        });

        enableBtn?.addEventListener('click', async () => {
            enableBtn.disabled = true;

            try {
                if (typeof window.enableAwaanPushNotifications !== 'function') {
                    throw new Error('Push script not loaded yet. Refresh and try again.');
                }

                const ok = await window.enableAwaanPushNotifications();

                if (!ok && Notification.permission === 'denied' && hint) {
                    hint.textContent = isAr
                        ? 'تم حظر الإشعارات. افتح إعدادات الموقع في المتصفح وفعّل الإشعارات.'
                        : 'Notifications are blocked. Open site settings in the browser and allow notifications.';
                    hint.classList.remove('hidden');
                }
            } catch (error) {
                console.warn(error);
            } finally {
                enableBtn.disabled = false;
            }
        });
    })();
</script>
