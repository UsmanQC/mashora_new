<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class WebManifestController extends Controller
{
    public function __invoke(string $app = 'patient'): JsonResponse
    {
        /** @var array<string, mixed>|null $appConfig */
        $appConfig = config("pwa.apps.{$app}");

        if (! is_array($appConfig)) {
            abort(404);
        }

        $description = trim((string) ($appConfig['description'] ?? ''));

        if ($description === '') {
            $description = $app === 'doctor'
                ? 'بوابة أوان للأطباء — إدارة المواعيد والجلسات والمرضى بسهولة وأمان.'
                : 'منصة أوان للرعاية النفسية — احجز جلسات أونلاين مع مختصين مرخصين بخصوصية وأمان.';
        }

        $startUrl = (string) ($appConfig['start_url'] ?? '/patient');

        $icons = collect((array) config('pwa.icons', []))
            ->map(static fn (array $icon): array => [
                'src' => '/'.ltrim((string) ($icon['path'] ?? ''), '/'),
                'sizes' => (string) ($icon['sizes'] ?? '192x192'),
                'type' => 'image/png',
                'purpose' => (string) ($icon['purpose'] ?? 'any'),
            ])
            ->values()
            ->all();

        return response()->json([
            'id' => $startUrl,
            'name' => (string) ($appConfig['name'] ?? 'Awaan'),
            'short_name' => (string) ($appConfig['short_name'] ?? 'Awaan'),
            'description' => $description,
            'lang' => (string) config('pwa.lang', 'ar'),
            'dir' => (string) config('pwa.dir', 'auto'),
            'start_url' => $startUrl,
            'scope' => (string) ($appConfig['scope'] ?? $startUrl),
            'display' => (string) config('pwa.display', 'standalone'),
            'orientation' => (string) config('pwa.orientation', 'portrait-primary'),
            'background_color' => (string) ($appConfig['background_color'] ?? '#F3F5F9'),
            'theme_color' => (string) ($appConfig['theme_color'] ?? '#10B981'),
            'categories' => ['health', 'medical'],
            'icons' => $icons,
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
        ]);
    }
}
