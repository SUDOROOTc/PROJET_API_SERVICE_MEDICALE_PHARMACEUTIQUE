<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\FirebaseTokenVerifier;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateFirebaseToken
{
    public function __construct(private readonly FirebaseTokenVerifier $tokenVerifier)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return $this->unauthorized('Token Bearer manquant.');
        }

        try {
            $claims = $this->tokenVerifier->verify($token);
        } catch (Throwable $exception) {
            return $this->unauthorized('Token Firebase invalide.');
        }

        $firebaseUid = (string) ($claims['sub'] ?? '');
        if ($firebaseUid === '') {
            return $this->unauthorized('Identifiant utilisateur manquant dans le token.');
        }

        $user = User::query()->updateOrCreate(
            ['firebase_uid' => $firebaseUid],
            [
                'name' => is_string($claims['name'] ?? null) ? $claims['name'] : null,
                'email' => is_string($claims['email'] ?? null) ? $claims['email'] : null,
                'phone_number' => is_string($claims['phone_number'] ?? null) ? $claims['phone_number'] : null,
                'avatar_url' => is_string($claims['picture'] ?? null) ? $claims['picture'] : null,
                'firebase_sign_in_provider' => $this->extractProvider($claims),
                'firebase_auth_time' => $this->extractAuthTime($claims),
                'firebase_raw_claims' => $claims,
                'email_verified_at' => ($claims['email_verified'] ?? false) === true ? now() : null,
                'password' => null,
                'last_seen_at' => now(),
            ]
        );

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Compte utilisateur inactif.',
                'errors' => [
                    'authorization' => ['Ce compte est desactive.'],
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('firebase_uid', $firebaseUid);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function extractProvider(array $claims): ?string
    {
        $provider = $claims['firebase']['sign_in_provider'] ?? null;

        return is_string($provider) && $provider !== '' ? $provider : null;
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function extractAuthTime(array $claims): ?Carbon
    {
        $authTime = $claims['auth_time'] ?? null;

        if (! is_numeric($authTime)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $authTime);
    }

    protected function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => [
                'authorization' => ['Authentification requise.'],
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
