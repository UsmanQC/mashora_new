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
        class="fixed inset-x-3 top-[max(0.75rem,env(safe-area-inset-top))] z-[100] mx-auto max-w-md rounded-2xl border border-amber-300 bg-amber-50 p-4 shadow-2xl"
        dir="{{ $isAr ? 'rtl' : 'ltr' }}"
    >
        <p class="text-sm font-bold text-amber-950">
            {{ $isAr ? 'إعداد الإشعارات ناقص على السيرفر' : 'Push is not configured on the server' }}
        </p>
        <p class="mt-1 text-xs leading-relaxed text-amber-900">
            {{ $isAr
                ? 'أضف FIREBASE_WEB_* و FIREBASE_VAPID_KEY في ملف .env ثم نفّذ php artisan config:clear'
                : 'Add FIREBASE_WEB_* and FIREBASE_VAPID_KEY to .env, then run php artisan config:clear' }}
        </p>
    </div>
@endif
