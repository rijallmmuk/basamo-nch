<?php

namespace App\Http\Controllers\Portal;

use App\Filament\Resources\UmkmProfiles\Schemas\UmkmProfileForm;
use App\Http\Controllers\Controller;
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
        $profile?->loadMissing(['products' => fn ($q) => $q->latest()]);

        return view('portal.umkm.index', [
            'profile' => $profile,
            'kategori' => UmkmProfileForm::KATEGORI,
        ]);
    }

    /** Form profil usaha (buat bila belum ada, atau ubah). */
    public function editProfile(): View
    {
        return view('portal.umkm.profile', [
            'profile' => auth()->user()->umkmProfile,
            'kategori' => UmkmProfileForm::KATEGORI,
        ]);
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_usaha' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'in:'.implode(',', array_keys(UmkmProfileForm::KATEGORI))],
            'whatsapp' => ['required', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]);

        $this->umkm->saveProfile(auth()->user(), $data);

        return redirect()->route('portal.umkm.index')
            ->with('success', 'Profil usaha tersimpan.');
    }
}
