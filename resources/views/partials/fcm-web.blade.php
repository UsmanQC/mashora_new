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
@endphp

@if ($enabled)
    <script>
        window.__AWAAN_FCM__ = {!! json_encode($fcmBootstrap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    </script>

    @include('partials.pwa-notification-prompt')
@endif
