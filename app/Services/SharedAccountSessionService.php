<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SharedAccountSessionService
{
    public const REFERENCE_KEY = 'shared_admin.session_reference';

    public const STARTED_AT_KEY = 'shared_admin.started_at';

    public function start(User $user, Request $request): void
    {
        $request->session()->put([
            self::REFERENCE_KEY => (string) Str::uuid(),
            self::STARTED_AT_KEY => now()->toIso8601String(),
        ]);

        activity('autentikasi')
            ->causedBy($user)
            ->event('login')
            ->withProperties(['session' => $this->auditContext($request)])
            ->log('Sesi admin dimulai.');
    }

    public function finish(User $user, Request $request): void
    {
        if (! $this->isIdentified($request)) {
            return;
        }

        activity('autentikasi')
            ->causedBy($user)
            ->event('logout')
            ->withProperties(['session' => $this->auditContext($request)])
            ->log('Sesi admin diakhiri.');
    }

    public function isIdentified(Request $request): bool
    {
        return $request->hasSession()
            && filled($request->session()->get(self::REFERENCE_KEY))
            && filled($request->session()->get(self::STARTED_AT_KEY));
    }

    /** @return array<string, string> */
    public function auditContext(Request $request): array
    {
        if (! $this->isIdentified($request)) {
            return [];
        }

        return [
            'session_reference' => (string) $request->session()->get(self::REFERENCE_KEY),
            'started_at' => (string) $request->session()->get(self::STARTED_AT_KEY),
            'ip_hash' => $this->fingerprint((string) $request->ip()),
            'user_agent_hash' => $this->fingerprint((string) $request->userAgent()),
        ];
    }

    private function fingerprint(string $value): string
    {
        return substr(hash_hmac('sha256', $value, (string) config('app.key')), 0, 24);
    }
}
