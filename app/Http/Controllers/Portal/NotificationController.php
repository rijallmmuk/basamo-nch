<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * Tandai semua notifikasi user sebagai sudah dibaca. Dipanggil (fetch POST) saat
     * modal notifikasi di header dibuka. Daftar notifikasi sendiri dirender di layout.
     */
    public function markAllRead(): Response
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
