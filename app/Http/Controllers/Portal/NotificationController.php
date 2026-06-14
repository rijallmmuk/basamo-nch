<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $notifications = $user->notifications()->latest()->paginate(20);

        // Tandai semua sebagai sudah dibaca saat halaman dibuka.
        $user->unreadNotifications->markAsRead();

        return view('portal.notifications.index', compact('notifications'));
    }
}
