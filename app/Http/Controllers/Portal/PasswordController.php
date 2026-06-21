<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /** Halaman ganti sandi (dipakai untuk paksa-ganti login pertama). */
    public function edit(): View
    {
        return view('portal.auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Paksa-ganti login pertama: warga sudah autentik via OTP → tak perlu sandi lama.
        // Ganti sandi biasa: wajib verifikasi sandi lama (cegah sesi dibajak mengganti sandi).
        $rules = [
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];

        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

        // Hook `User::saving` otomatis hapus OTP awal & lepas flag wajib-ganti saat sandi berubah.
        $user->forceFill(['password' => $data['password']])->save();

        return redirect()->route('portal.home')
            ->with('info', 'Kata sandi berhasil diperbarui.');
    }
}
