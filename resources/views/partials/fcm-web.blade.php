{{--
  Exposes FCM web config for authenticated PWA portals.
  @var string $portal patient|doctor
--}}
@php
    $web = config('push.web');
    $enabled = filled($web['api_key'] ?? null)
        && filled($web['project_id'] ?? null)
        && filled($web['messaging_sender_id'] ?? null)
        && filled($web['app_id'] ?? null)
        && filled($web['vapid_key'] ?? null);

    $registerUrl = ($portal ?? 'patient') === 'doctor'
        ? route('doctor.device-token.store')
        : route('patient.device-token.store');

    $fcmBootstrap = [
        'enabled' => true,
        'portal' => $portal ?? 'patient',
        'registerUrl' => $registerUrl,
        'vapidKey' => $web['vapid_key'] ?? null,
        'firebase' => [
            'apiKey' => $web['api_key'] ?? null,
            'authDomain' => $web['auth_domain'] ?? null,
            'projectId' => $web['project_id'] ?? null,
            'storageBucket' => $web['storage_bucket'] ?? null,
            'messagingSenderId' => $web['messaging_sender_id'] ?? null,
            'appId' => $web['app_id'] ?? null,
            'measurementId' => $web['measurement_id'] ?? null,
        ],
    ];

    $isAr = app()->getLocale() === 'ar';
@endphp

@if ($enabled)
    <script>
        window.__AWAAN_FCM__ = {!! json_encode($fcmBootstrap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    </script>

    @include('partials.pwa-notification-prompt')
@else
    <div
        id="awaan-push-misconfigured"
        data-awaan-push="misconfigured-v2"
        style="position:fixed;left:12px;right:12px;top:max(12px,env(safe-area-inset-top));z-index:2147483646;margin:0 auto;max-width:28rem;border-radius:1rem;border:1px solid #fcd34d;background:#fffbeb;padding:1rem;box-shadow:0 20px 40px rgba(0,0,0,.18);"
        dir="{{ $isAr ? 'rtl' : 'ltr' }}"
    >
        <p style="margin:0;font-size:14px;font-weight:700;color:#78350f;">
            {{ $isAr ? 'إعداد الإشعارات ناقص على السيرفر' : 'Push is not configured on the server' }}
        </p>
        <p style="margin:6px 0 0;font-size:12px;line-height:1.45;color:#92400e;">
            {{ $isAr
                ? 'أضف FIREBASE_WEB_* و FIREBASE_VAPID_KEY في .env ثم: php artisan config:clear'
                : 'Add FIREBASE_WEB_* and FIREBASE_VAPID_KEY to .env, then run: php artisan config:clear' }}
        </p>
    </div>
    <script>
        (() => {
            const el = document.getElementById('awaan-push-misconfigured');
            if (el) {
                document.documentElement.appendChild(el);
            }
        })();
    </script>
@endif
