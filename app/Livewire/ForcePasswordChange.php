<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Modal pemblokir ganti sandi untuk admin yang masih memakai sandi awal (OTP).
 * Dirender lewat render hook panel (BODY_END) saat `must_change_password`, menutupi
 * layar sampai sandi diganti. HANYA ganti sandi (kontak dilengkapi nanti lewat Profil).
 * Sandi bebas, cukup minimal 8 karakter.
 *
 * Setelah berhasil TIDAK langsung dilempar ke login: panel memakai AuthenticateSession
 * sehingga ganti sandi membatalkan sesi. Maka tampilkan langkah sukses + tombol "Masuk
 * lagi" agar admin paham harus login ulang dengan sandi baru (bukan logout senyap).
 */
class ForcePasswordChange extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    /** Sandi sudah diganti → tampilkan pesan "silakan masuk lagi". */
    public bool $saved = false;

    public function save(): void
    {
        $data = $this->validate([
            // Sandi bebas, cukup minimal 8 karakter (tanpa syarat huruf/angka).
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Hook User::saving menghapus initial_otp & melepas must_change_password.
        auth()->user()->forceFill(['password' => $data['password']])->save();

        // Jangan redirect senyap — tampilkan konfirmasi + arahan login ulang di modal.
        $this->saved = true;
    }

    public function render()
    {
        return view('filament.force-password-change');
    }
}
