<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class FirebaseAccessTokenResolver
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * @return array{project_id: string, client_email: string, private_key: string}|null
     */
    public function credentials(): ?array
    {
        $path = $this->credentialsPath();

        if ($path === null || ! is_readable($path)) {
            return null;
        }

        try {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            Log::warning('Firebase credentials JSON could not be parsed.', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $projectId = (string) (config('push.firebase_project_id') ?: ($decoded['project_id'] ?? ''));
        $clientEmail = (string) ($decoded['client_email'] ?? '');
        $privateKey = (string) ($decoded['private_key'] ?? '');

        if ($projectId === '' || $clientEmail === '' || $privateKey === '') {
            Log::warning('Firebase credentials JSON is missing required fields.', ['path' => $path]);

            return null;
        }

        return [
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];
    }

    public function accessToken(): ?string
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return null;
        }

        $cacheKey = 'firebase.fcm.access_token.'.hash('sha256', $credentials['client_email']);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $token = $this->fetchAccessToken($credentials);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        Cache::put($cacheKey, $token['access_token'], now()->addSeconds($token['expires_in']));

        return $token['access_token'];
    }

    public function projectId(): ?string
    {
        return $this->credentials()['project_id'] ?? null;
    }

    public function credentialsPath(): ?string
    {
        $configured = trim((string) config('push.firebase_credentials'));

        if ($configured === '') {
            return null;
        }

        if (is_file($configured)) {
            return $configured;
        }

        $fromBase = base_path($configured);
        if (is_file($fromBase)) {
            return $fromBase;
        }

        return null;
    }

    /**
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     * @return array{access_token: string, expires_in: int}
     */
    private function fetchAccessToken(array $credentials): array
    {
        $assertion = $this->makeJwt($credentials);

        $response = Http::asForm()
            ->timeout(15)
            ->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to obtain Firebase access token: '.$response->body());
        }

        $accessToken = (string) $response->json('access_token');
        $expiresIn = (int) $response->json('expires_in', 3600);

        if ($accessToken === '') {
            throw new RuntimeException('Firebase token response did not include an access_token.');
        }

        return [
            'access_token' => $accessToken,
            'expires_in' => max(60, $expiresIn - 60),
        ];
    }

    /**
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     */
    private function makeJwt(array $credentials): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$claims;

        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException('Firebase private_key could not be loaded.');
        }

        $signature = '';
        $signed = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $signed) {
            throw new RuntimeException('Failed to sign Firebase JWT.');
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
