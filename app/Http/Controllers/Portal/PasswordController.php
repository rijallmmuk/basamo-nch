<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Rules\NotInitialPassword;
use App\Services\FirstLoginPasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Penyimpan sandi baru untuk modal wajib-ganti di beranda portal.
 *
 * HANYA melayani login pertama dengan sandi awal bersama. Ganti sandi biasa
 * (yang meminta sandi lama) ada di Profil, lihat ProfileController::updatePassword.
 */
class PasswordController extends Controller
{
    public function update(Request $request, FirstLoginPasswordService $firstLoginPassword): RedirectResponse
    {
        // Warga sudah autentik dan sandi lamanya dipakai bersama, jadi memintanya
        // kembali tidak menambah jaminan apa pun.
        abort_unless($request->user()->must_change_password, 403);

        $data = $request->validate([
            // Sandi bebas, cukup minimal 8 karakter (tanpa syarat huruf/angka).
            'password' => ['required', 'string', 'min:8', 'confirmed', new NotInitialPassword],
        ]);

        $user = $firstLoginPassword->change($request->user(), $data['password']);

        if ($user === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['login' => 'Password awal sudah diganti oleh sesi lain. Masuk kembali dengan password baru.']);
        }

        Auth::setUser($user);

        // Pertahankan sesi pemenang; sesi lain akan ditolak AuthenticateSession.
        $request->session()->put('password_hash_'.Auth::getDefaultDriver(), $user->getAuthPassword());

        return redirect()->route('portal.home')
            ->with('info', 'Kata sandi berhasil diperbarui.');
    }
}
