<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],   // di-hash via cast
            'must_change_password' => false,
            'initial_otp' => null,             // OTP plaintext dihapus setelah diganti
            'otp_expires_at' => null,
        ])->save();

        return redirect()->route('portal.home')
            ->with('info', 'Kata sandi berhasil diperbarui.');
    }
}
