<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\UmkmCategory;
use App\Services\UmkmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UmkmController extends Controller
{
    public function __construct(private readonly UmkmService $umkm) {}

    /** Dashboard "Produk Saya": ringkasan profil + daftar produk milik pemilik. */
    public function index(): View
    {
        $profile = auth()->user()->umkmProfile;
        $profile?->loadMissing(['category', 'products' => fn ($q) => $q->latest()]);

        return view('portal.umkm.index', ['profile' => $profile]);
    }

    /** Form profil usaha (buat bila belum ada, atau ubah). */
    public function editProfile(): View
    {
        return view('portal.umkm.profile', [
            'profile' => auth()->user()->umkmProfile,
            'kategori' => UmkmCategory::options(),
        ]);
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        // Tanpa deskripsi profil (keputusan user 2026-07-03); alamat lengkap wajib —
        // konsisten dgn form pengajuan akses UMKM.
        $data = $request->validate([
            'nama_usaha' => ['required', 'string', 'max:255'],
            'umkm_category_id' => ['required', 'integer', 'exists:umkm_categories,id'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ().\-\/]{6,18}$/'],
            'alamat' => ['required', 'string', 'max:500'],
        ], [
            'whatsapp.regex' => 'Isi nomor WhatsApp yang valid, mis. 08123456789.',
        ]);

        $this->umkm->saveProfile(auth()->user(), $data);

        return redirect()->route('portal.umkm.index')
            ->with('success', 'Profil usaha tersimpan.');
    }
}
