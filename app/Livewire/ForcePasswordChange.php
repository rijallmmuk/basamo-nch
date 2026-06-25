<?php

namespace App\Livewire;

use App\Support\PhoneNumber;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Modal pemblokir ganti sandi untuk admin yang masih memakai sandi awal (OTP).
 * Dirender lewat render hook panel (BODY_END) saat `must_change_password`, menutupi
 * layar sampai sandi diganti. No. HP & email opsional (TIDAK wajib). Sandi bebas,
 * cukup minimal 8 karakter.
 *
 * Setelah berhasil TIDAK langsung dilempar ke login: panel memakai AuthenticateSession
 * sehingga ganti sandi membatalkan sesi. Maka tampilkan langkah sukses + tombol "Masuk
 * lagi" agar admin paham harus login ulang dengan sandi baru (bukan logout senyap).
 */
class ForcePasswordChange extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public string $phone = '';

    public string $email = '';

    /** Sandi sudah diganti → tampilkan pesan "silakan masuk lagi". */
    public bool $saved = false;

    public function save(): void
    {
        $data = $this->validate([
            // Sandi bebas, cukup minimal 8 karakter (tanpa syarat huruf/angka).
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
        ]);

        $user = auth()->user();
        $attributes = ['password' => $data['password']];

        // Lengkapi kontak hanya bila diisi (keduanya opsional).
        if (filled($this->phone)) {
            $attributes['phone'] = PhoneNumber::normalize($this->phone);
        }

        if (filled($this->email)) {
            $attributes['email'] = Str::lower(trim($this->email));
        }

        // Hook User::saving menghapus initial_otp & melepas must_change_password.
        $user->forceFill($attributes)->save();

        // Jangan redirect senyap — tampilkan konfirmasi + arahan login ulang di modal.
        $this->saved = true;
    }

    public function render()
    {
        return view('filament.force-password-change');
    }
}
