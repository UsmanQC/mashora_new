{{-- Inline styles + move to <html> so overflow-hidden body shells cannot clip this on mobile. --}}
@php
    $isAr = app()->getLocale() === 'ar';
@endphp

<div
    id="awaan-push-enable"
    data-awaan-push="enable-v2"
    style="position:fixed;left:12px;right:12px;top:max(12px,env(safe-area-inset-top));z-index:2147483646;margin:0 auto;max-width:28rem;border-radius:1rem;border:1px solid #a7f3d0;background:#ffffff;padding:1rem;box-shadow:0 20px 40px rgba(0,0,0,.18);font-family:inherit;"
    dir="{{ $isAr ? 'rtl' : 'ltr' }}"
    role="dialog"
    aria-live="polite"
>
    <p style="margin:0;font-size:14px;font-weight:700;color:#18181b;">
        {{ $isAr ? 'تفعيل الإشعارات' : 'Enable notifications' }}
    </p>
    <p id="awaan-push-enable-body" style="margin:6px 0 0;font-size:12px;line-height:1.45;color:#52525b;">
        {{ $isAr
            ? 'اضغط للسماح بالإشعارات حتى يصلك تنبيه بالمواعيد والرسائل.'
            : 'Tap to allow notifications for appointments and messages.' }}
    </p>
    <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;">
        <button
            type="button"
            id="awaan-push-enable-btn"
            style="border:0;border-radius:12px;background:#10B981;color:#fff;font-size:12px;font-weight:700;padding:10px 16px;"
        >
            {{ $isAr ? 'السماح بالإشعارات' : 'Allow notifications' }}
        </button>
        <button
            type="button"
            id="awaan-push-enable-dismiss"
            style="border:1px solid #e4e4e7;border-radius:12px;background:#fff;color:#3f3f46;font-size:12px;font-weight:600;padding:10px 16px;"
        >
            {{ $isAr ? 'لاحقاً' : 'Later' }}
        </button>
    </div>
    <p id="awaan-push-enable-hint" style="display:none;margin:8px 0 0;font-size:11px;line-height:1.4;color:#b45309;"></p>
</div>

<script>
    (() => {
        const root = document.getElementById('awaan-push-enable');
        if (!root) {
            return;
        }

        // Escape overflow-hidden ancestors used by the mobile shells.
        document.documentElement.appendChild(root);

        const enableBtn = document.getElementById('awaan-push-enable-btn');
        const dismissBtn = document.getElementById('awaan-push-enable-dismiss');
        const hint = document.getElementById('awaan-push-enable-hint');
        const body = document.getElementById('awaan-push-enable-body');
        const isAr = @json($isAr);

        const setHint = (message) => {
            if (!hint) {
                return;
            }
            hint.textContent = message || '';
            hint.style.display = message ? 'block' : 'none';
        };

        const hide = () => {
            root.style.display = 'none';
        };

        if (window.isSecureContext && 'Notification' in window && Notification.permission === 'granted') {
            hide();
            try {
                localStorage.setItem('awaan-push-enabled', '1');
            } catch (e) {}
        }

        try {
            if (localStorage.getItem('awaan-push-enabled') === '1') {
                hide();
            }
        } catch (e) {}

        if (!window.isSecureContext) {
            if (body) {
                body.textContent = isAr
                    ? 'الإشعارات تحتاج فتح الموقع عبر HTTPS وليس http.'
                    : 'Notifications require opening the site over HTTPS, not http.';
            }
            if (enableBtn) {
                enableBtn.disabled = true;
                enableBtn.style.opacity = '0.5';
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

        try {
            if (
                sessionStorage.getItem('awaan-push-dismissed') === '1'
                && window.isSecureContext
                && 'Notification' in window
                && Notification.permission === 'default'
            ) {
                hide();
            }
        } catch (e) {}

        enableBtn?.addEventListener('click', async () => {
            if (!window.isSecureContext) {
                setHint(isAr ? 'استخدم رابط HTTPS أولاً.' : 'Use the HTTPS link first.');
                return;
            }

            enableBtn.disabled = true;
            setHint('');

            try {
                // Wait briefly for /js/awaan-fcm.js module to attach the helper.
                const started = Date.now();
                while (typeof window.enableAwaanPushNotifications !== 'function' && Date.now() - started < 5000) {
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }

                if (typeof window.enableAwaanPushNotifications !== 'function') {
                    setHint(isAr
                        ? 'سكربت الإشعارات لم يحمّل. ارفع public/js/awaan-fcm.js وحدّث الصفحة.'
                        : 'Push script not loaded. Upload public/js/awaan-fcm.js and refresh.');
                    return;
                }

                const ok = await window.enableAwaanPushNotifications();

                if (ok) {
                    hide();
                    return;
                }

                if ('Notification' in window && Notification.permission === 'denied') {
                    setHint(isAr
                        ? 'تم حظر الإشعارات من إعدادات الموقع في المتصفح.'
                        : 'Notifications are blocked in this site’s browser settings.');
                } else {
                    setHint(isAr
                        ? 'لم يتم تفعيل الإشعارات. حاول مرة أخرى.'
                        : 'Notifications were not enabled. Try again.');
                }
            } catch (error) {
                console.warn(error);
                setHint(isAr ? 'حدث خطأ. حاول مرة أخرى.' : 'Something went wrong. Try again.');
            } finally {
                enableBtn.disabled = false;
            }
        });
    })();
</script>
