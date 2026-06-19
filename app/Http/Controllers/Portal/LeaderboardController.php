<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    /**
     * Peringkat XP warga se-nagari (urut total_xp).
     */
    public function index(): View
    {
        $user = auth()->user();

        $warga = User::query()
            ->where('role', 'warga')
            ->where('nagari_id', $user->nagari_id)
            ->orderByDesc('total_xp')
            ->orderBy('name')
            ->paginate(20);

        $myRank = $this->rankOf($user);
        $totalWarga = User::where('role', 'warga')
            ->where('nagari_id', $user->nagari_id)
            ->count();

        return view('portal.leaderboard.index', compact('warga', 'myRank', 'totalWarga'));
    }

    /**
     * Peringkat user = jumlah warga senagari dengan XP lebih tinggi + 1.
     */
    private function rankOf(User $user): int
    {
        return User::where('role', 'warga')
            ->where('nagari_id', $user->nagari_id)
            ->where('total_xp', '>', $user->total_xp)
            ->count() + 1;
    }
}
