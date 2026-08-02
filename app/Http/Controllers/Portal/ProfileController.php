<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Rules\NotInitialPassword;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\ImageOptimizer;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user()->load([
            'penduduk.agama',
            'penduduk.pendidikan',
            'penduduk.statusPerkawinan',
            'penduduk.pekerjaan',
        ]);

        return view('portal.profile.edit', ['user' => $user]);
    }

    /**
     * Perbarui foto profil. Gambar sudah di-crop & dikompres di klien (<50 KB, JPEG
     * 160×160 = 2× tampilan terbesar); `max:50` di sini sebagai pengaman terakhir.
     * Koleksi singleFile otomatis menggantikan foto lama.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
        ]);

        $file = $request->file('avatar');
        $optimizer = app(ImageOptimizer::class);
        $optimizedPath = $optimizer->optimizeToTemp($file->getRealPath(), 320); // 320x320 for avatar

        if ($optimizedPath) {
            $namaAsli = $file->getClientOriginalName() ?: basename($optimizedPath);
            try {
                $request->user()->addMedia($optimizedPath)
                    ->usingFileName(pathinfo($namaAsli, PATHINFO_FILENAME).'.webp')
                    ->toMediaCollection('avatar');
            } finally {
                @unlink($optimizedPath);
            }
        } else {
            // Fallback
            $request->user()->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return redirect()->route('portal.profile.edit')
            ->with('success', 'Foto profil diperbarui.');
    }

    /** Perbarui kontak (email & No. HP). Data kependudukan tidak disentuh di sini. */
    public function updateContact(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            // Format sama dengan nomor WhatsApp pada form profil UMKM Filament.
            // — dulu field ini TANPA validasi format sama sekali, bisa ketiban teks apa pun.
            'phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNumber::REGEX],
        ], [
            'phone.regex' => 'Isi nomor HP yang valid, mis. 08123456789.',
        ]);

        $user->forceFill([
            'email' => filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null,
            'phone' => PhoneNumber::normalize($data['phone'] ?? null),
        ])->save();

        return redirect()->route('portal.profile.edit')
            ->with('success', 'Kontak diperbarui.');
    }

    /** Ganti sandi dari halaman profil — wajib verifikasi sandi lama. */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            // Sandi bebas, cukup minimal 8 karakter (konsisten dengan alur login pertama).
            'password' => ['required', 'string', 'min:8', 'confirmed', new NotInitialPassword],
        ]);

        // Hook `User::saving` otomatis membersihkan flag wajib-ganti saat sandi berubah.
        $request->user()->forceFill(['password' => $data['password']])->save();

        return redirect()->route('portal.profile.edit')
            ->with('success', 'Kata sandi diperbarui.');
    }
}
