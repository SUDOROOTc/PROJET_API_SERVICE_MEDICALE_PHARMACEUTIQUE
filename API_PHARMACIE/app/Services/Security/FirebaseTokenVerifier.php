<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseTokenVerifier
{
    /**
     * Verify a Firebase ID token and return validated claims.
     *
     * @return array<string, mixed>
     */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeSegment($encodedHeader);
        $payload = $this->decodeSegment($encodedPayload);

        if (! is_array($header) || ! is_array($payload)) {
            throw new RuntimeException('Invalid token payload.');
        }

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new RuntimeException('Unsupported token algorithm.');
        }

        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new RuntimeException('Missing key ID in token header.');
        }

        $certificates = $this->getCertificates();
        $certificate = $certificates[$kid] ?? null;

        if (! is_string($certificate) || $certificate === '') {
            throw new RuntimeException('Token key is unknown or expired.');
        }

        $signature = $this->base64UrlDecode($encodedSignature);
        if ($signature === false) {
            throw new RuntimeException('Invalid token signature encoding.');
        }

        $signedData = $encodedHeader.'.'.$encodedPayload;
        $signatureValid = openssl_verify($signedData, $signature, $certificate, OPENSSL_ALGO_SHA256) === 1;

        if (! $signatureValid) {
            throw new RuntimeException('Token signature verification failed.');
        }

        $this->validateClaims($payload);

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    protected function getCertificates(): array
    {
        $url = (string) config('services.firebase.certificates_url');

        return Cache::remember('firebase:public-certificates', now()->addMinutes(50), function () use ($url): array {
            $response = Http::timeout(5)->acceptJson()->get($url);

            if (! $response->ok()) {
                throw new RuntimeException('Could not load Firebase public certificates.');
            }

            $data = $response->json();

            return is_array($data) ? $data : [];
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function validateClaims(array $payload): void
    {
        $projectId = (string) config('services.firebase.project_id');

        if ($projectId === '') {
            throw new RuntimeException('Firebase project ID is not configured.');
        }

        $now = time();
        $issuer = 'https://securetoken.google.com/'.$projectId;

        if (($payload['aud'] ?? null) !== $projectId) {
            throw new RuntimeException('Invalid audience claim.');
        }

        if (($payload['iss'] ?? null) !== $issuer) {
            throw new RuntimeException('Invalid issuer claim.');
        }

        $subject = $payload['sub'] ?? null;
        if (! is_string($subject) || $subject === '') {
            throw new RuntimeException('Invalid subject claim.');
        }

        $exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;
        if ($exp <= $now) {
            throw new RuntimeException('Token has expired.');
        }

        $iat = isset($payload['iat']) ? (int) $payload['iat'] : 0;
        if ($iat > ($now + 300)) {
            throw new RuntimeException('Token issued in the future.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeSegment(string $segment): array
    {
        $decoded = $this->base64UrlDecode($segment);

        if ($decoded === false) {
            throw new RuntimeException('Invalid token encoding.');
        }

        $json = json_decode($decoded, true);

        return is_array($json) ? $json : [];
    }

    protected function base64UrlDecode(string $value): string|false
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $value = strtr($value, '-_', '+/');

        return base64_decode($value, true);
    }
}
