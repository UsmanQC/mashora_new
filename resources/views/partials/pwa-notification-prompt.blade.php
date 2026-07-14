{{-- Visible by default so mobile users always see it; JS only hides when push is already active. --}}
@php
    $isAr = app()->getLocale() === 'ar';
@endphp

<div
    id="awaan-push-enable"
    class="fixed inset-x-3 top-[max(0.75rem,env(safe-area-inset-top))] z-[100] mx-auto max-w-md rounded-2xl border border-emerald-200 bg-white p-4 shadow-2xl"
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
            <p id="awaan-push-enable-body" class="mt-1 text-xs leading-relaxed text-zinc-600">
                {{ $isAr
                    ? 'اضغط للسماح بالإشعارات حتى يصلك تنبيه بالمواعيد والرسائل.'
                    : 'Tap to allow notifications for appointments and messages.' }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    id="awaan-push-enable-btn"
                    class="rounded-xl bg-[#10B981] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-600"
                >
                    {{ $isAr ? 'السماح بالإشعارات' : 'Allow notifications' }}
                </button>
                <button
                    type="button"
                    id="awaan-push-enable-dismiss"
                    class="rounded-xl border border-zinc-200 px-4 py-2.5 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50"
                >
                    {{ $isAr ? 'لاحقاً' : 'Later' }}
                </button>
            </div>
            <p id="awaan-push-enable-hint" class="mt-2 hidden text-[11px] leading-relaxed text-amber-700"></p>
        </div>
    </div>
</div>

<script>
    (() => {
        const root = document.getElementById('awaan-push-enable');
        const enableBtn = document.getElementById('awaan-push-enable-btn');
        const dismissBtn = document.getElementById('awaan-push-enable-dismiss');
        const hint = document.getElementById('awaan-push-enable-hint');
        const body = document.getElementById('awaan-push-enable-body');
        const isAr = @json($isAr);

        const setHint = (message) => {
            if (!hint) {
                return;
            }

            hint.textContent = message;
            hint.classList.toggle('hidden', !message);
        };

        const hide = () => root?.classList.add('hidden');

        // Hide only when already granted (token sync happens in app.js).
        if (window.isSecureContext && 'Notification' in window && Notification.permission === 'granted') {
            hide();
        }

        if (!window.isSecureContext) {
            if (body) {
                body.textContent = isAr
                    ? 'الإشعارات تحتاج فتح الموقع عبر HTTPS وليس http.'
                    : 'Notifications require opening the site over HTTPS, not http.';
            }
            if (enableBtn) {
                enableBtn.disabled = true;
                enableBtn.classList.add('opacity-50');
            }
            setHint(isAr
                ? 'افتح رابط https:// الخاص بالموقع من الجوال.'
                : 'Open the site with its https:// URL on your phone.');
        }

        dismissBtn?.addEventListener('click', () => {
            hide();
            try {
                sessionStorage.setItem('awaan-push-dismissed', '1');
            } catch (e) {}
        });

        if (sessionStorage.getItem('awaan-push-dismissed') === '1'
            && window.isSecureContext
            && 'Notification' in window
            && Notification.permission === 'default') {
            hide();
        }

        enableBtn?.addEventListener('click', async () => {
            if (!window.isSecureContext) {
                setHint(isAr
                    ? 'استخدم رابط HTTPS أولاً.'
                    : 'Use the HTTPS link first.');
                return;
            }

            enableBtn.disabled = true;
            setHint('');

            try {
                if (typeof window.enableAwaanPushNotifications !== 'function') {
                    setHint(isAr
                        ? 'سكربت الإشعارات لم يحمّل بعد. حدّث الصفحة وحاول مرة أخرى.'
                        : 'Push script not loaded yet. Refresh and try again.');
                    return;
                }

                const ok = await window.enableAwaanPushNotifications();

                if (ok) {
                    hide();
                    return;
                }

                if (Notification.permission === 'denied') {
                    setHint(isAr
                        ? 'تم حظر الإشعارات. من إعدادات الموقع في المتصفح فعّل الإشعارات ثم أعد المحاولة.'
                        : 'Notifications are blocked. In site settings, allow notifications, then try again.');
                } else {
                    setHint(isAr
                        ? 'لم يتم تفعيل الإشعارات. حاول مرة أخرى.'
                        : 'Notifications were not enabled. Please try again.');
                }
            } catch (error) {
                console.warn(error);
                setHint(isAr ? 'حدث خطأ. حاول مرة أخرى.' : 'Something went wrong. Please try again.');
            } finally {
                enableBtn.disabled = false;
            }
        });
    })();
</script>
