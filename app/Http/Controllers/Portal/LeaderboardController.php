<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    /**
     * Peringkat XP warga se-desa (urut total_xp). Hanya warga aktif.
     */
    public function index(): View
    {
        $user = auth()->user();

        $warga = $this->wargaQuery($user)
            ->orderByDesc('total_xp')
            ->orderBy('name')
            ->paginate(20);

        return view('portal.leaderboard.index', [
            'warga' => $warga,
            'ranks' => $this->competitionRanks($user, $warga),
            'myRank' => $this->rankOf($user),
            'totalWarga' => $this->wargaQuery($user)->count(),
        ]);
    }

    /** Kandidat peringkat: warga aktif se-desa. */
    private function wargaQuery(User $user): Builder
    {
        return User::query()
            ->where('role', 'warga')
            ->where('status', 'active')
            ->where('desa_id', $user->desa_id);
    }

    /**
     * Peringkat kompetisi (skor sama = peringkat sama) untuk baris di halaman ini,
     * konsisten dengan rankOf(). Hanya 1 query tambahan (peringkat baris pertama).
     *
     * @return array<int, int> user_id => peringkat
     */
    private function competitionRanks(User $user, LengthAwarePaginator $warga): array
    {
        $items = $warga->items();

        if ($items === []) {
            return [];
        }

        // Peringkat baris pertama: jumlah warga ber-XP lebih tinggi + 1
        // (menangani seri yang melintasi batas halaman).
        $rank = $this->wargaQuery($user)
            ->where('total_xp', '>', $items[0]->total_xp)
            ->count() + 1;

        $ranks = [];
        $prevXp = null;

        foreach ($items as $i => $w) {
            // XP berbeda dari baris sebelumnya → peringkat = posisi global baris ini.
            if ($i > 0 && $w->total_xp !== $prevXp) {
                $rank = $warga->firstItem() + $i;
            }

            $ranks[$w->id] = $rank;
            $prevXp = $w->total_xp;
        }

        return $ranks;
    }

    /**
     * Peringkat user = jumlah warga aktif sedesa dengan XP lebih tinggi + 1.
     */
    private function rankOf(User $user): int
    {
        return $this->wargaQuery($user)
            ->where('total_xp', '>', $user->total_xp ?? 0)
            ->count() + 1;
    }
}
