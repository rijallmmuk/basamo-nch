<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * Tandai semua notifikasi user sebagai sudah dibaca. Dipakai lonceng portal warga
     * (fetch → 204) & lonceng kustom panel admin (form POST → kembali ke halaman).
     */
    public function markAllRead(Request $request): Response|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson() ? response()->noContent() : back();
    }

    /**
     * Buka satu notifikasi: tandai notifikasi ITU sudah dibaca (angka lonceng berkurang 1),
     * lalu arahkan ke tujuannya. Hanya notifikasi milik sendiri (aman).
     */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification?->data['url'] ?? null;

        return redirect($this->safeInternalDestination($url, $request));
    }

    private function safeInternalDestination(mixed $url, Request $request): string
    {
        $fallback = $request->user()->hasRole('warga')
            ? route('portal.home', absolute: false)
            : '/panel';

        if (! is_string($url)
            || ! str_starts_with($url, '/')
            || str_starts_with($url, '//')
            || str_contains($url, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $url) === 1
            || parse_url($url, PHP_URL_HOST) !== null) {
            return $fallback;
        }

        return $url;
    }
}
