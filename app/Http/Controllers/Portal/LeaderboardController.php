<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    /**
     * Halaman leaderboard — DATA SEMENTARA (dummy).
     * Sistem poin nyata (LmsPointService) menyusul setelah kesepakatan skema penilaian.
     */
    public function index(): View
    {
        $user = auth()->user();

        // Data contoh untuk pratinjau tampilan. Belum terhubung sistem poin.
        $entries = collect([
            ['name' => 'Siti Nurhaliza', 'points' => 1280, 'modules' => 9],
            ['name' => 'Budi Santoso', 'points' => 1150, 'modules' => 8],
            ['name' => 'Andi Pratama', 'points' => 1040, 'modules' => 8],
            ['name' => 'Dewi Lestari', 'points' => 920, 'modules' => 7],
            ['name' => $user->name.' (kamu)', 'points' => 760, 'modules' => 6, 'is_me' => true],
            ['name' => 'Rizky Maulana', 'points' => 690, 'modules' => 5],
            ['name' => 'Putri Ayu', 'points' => 540, 'modules' => 4],
            ['name' => 'Joko Widodo', 'points' => 410, 'modules' => 3],
        ])->map(fn ($e, $i) => array_merge($e, ['rank' => $i + 1]));

        return view('portal.leaderboard.index', compact('entries'));
    }
}
