<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        // Sekalian lengkapi kontak (opsional) — terutama berguna saat login pertama.
        $rules = [
            // Sandi bebas, cukup minimal 8 karakter (tanpa syarat huruf/angka).
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

        // Hook `User::saving` otomatis hapus OTP awal & lepas flag wajib-ganti saat sandi berubah.
        $attributes = ['password' => $data['password']];

        // Kontak hanya diperbarui bila field dikirim (form selalu mengirim, walau kosong) —
        // cegah terhapus tak sengaja oleh request yang tak menyertakannya. Email huruf kecil;
        // No. HP dinormalkan 62xxx (konsisten form admin/warga).
        if ($request->has('email')) {
            $attributes['email'] = filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null;
        }

        if ($request->has('phone')) {
            $attributes['phone'] = PhoneNumber::normalize($data['phone'] ?? null);
        }

        $user->forceFill($attributes)->save();

        return redirect()->route('portal.home')
            ->with('info', 'Kata sandi berhasil diperbarui.');
    }
}
