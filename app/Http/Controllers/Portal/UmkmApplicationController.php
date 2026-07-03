<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PengajuanUmkmStatus;
use App\Http\Controllers\Controller;
use App\Models\UmkmCategory;
use App\Services\UmkmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengajuan akses UMKM mandiri oleh warga (di LUAR middleware umkm.owner):
 * warga tanpa akses mengirim profil usaha + satu produk lengkap → ditinjau
 * admin desa (setujui = akses aktif + lapak & produk tayang; tolak = beralasan,
 * boleh diperbaiki & diajukan ulang). Admin tetap bisa memberi akses langsung.
 */
class UmkmApplicationController extends Controller
{
    public function __construct(private readonly UmkmService $umkm) {}

    public function create(): View|RedirectResponse
    {
        $user = auth()->user();

        // Sudah pemilik → tak ada yang perlu diajukan.
        if ($user->hasUmkmAccess()) {
            return redirect()->route('portal.umkm.index');
        }

        $profile = $user->umkmProfile()->first();
        $product = $profile?->products()->with('media')->first();

        return view('portal.umkm.apply', [
            'profile' => $profile,
            'product' => $product,
            'kategori' => UmkmCategory::options(),
            // Bantuan deskripsi per kategori (umum saja — detail via WhatsApp):
            // panduan poin tampil saat kategori dipilih; contoh deskripsi jadi
            // bisa dipakai sekali klik lalu diubah seperlunya.
            'panduanMap' => UmkmCategory::whereNotNull('panduan_produk')->pluck('panduan_produk', 'id'),
            'contohMap' => UmkmCategory::whereNotNull('contoh_deskripsi')->pluck('contoh_deskripsi', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasUmkmAccess()) {
            return redirect()->route('portal.umkm.index');
        }

        // Query segar (bukan relasi ter-cache di instance user).
        $profile = $user->umkmProfile()->first();

        // Satu pengajuan berjalan per warga — tunggu keputusan admin dulu.
        if ($profile?->status_pengajuan === PengajuanUmkmStatus::Menunggu) {
            return redirect()->route('portal.umkm.ajukan')
                ->with('info', 'Pengajuanmu masih menunggu tinjauan Admin. Mohon bersabar, ya.');
        }

        $existingPhotos = $profile?->products()->first()?->getMedia('photos')->count() ?? 0;

        // Semua field pengajuan WAJIB (keputusan user 2026-07-02) — termasuk harga
        // & minimal satu foto produk (foto lama dari pengajuan sebelumnya dihitung).
        // Profil TANPA deskripsi (keputusan user 2026-07-03); alamat = alamat lengkap.
        $data = $request->validate([
            'nama_usaha' => ['required', 'string', 'max:255'],
            'umkm_category_id' => ['required', 'integer', 'exists:umkm_categories,id'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ().\-\/]{6,18}$/'],
            'alamat' => ['required', 'string', 'max:500'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'deskripsi_produk' => ['required', 'string', 'min:30', 'max:5000'],
            'harga' => ['required', 'integer', 'min:0', 'max:999999999'],
            'photos' => [Rule::requiredIf($existingPhotos === 0), 'array', 'max:'.UmkmService::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
        ], [
            'whatsapp.regex' => 'Isi nomor WhatsApp yang valid, mis. 08123456789.',
            'photos.required' => 'Unggah minimal satu foto produk.',
            'deskripsi_produk.min' => 'Jelaskan produkmu lebih lengkap (minimal 30 karakter) — ikuti panduan di bawah kolom deskripsi.',
        ]);

        $this->umkm->submitApplication(
            $user,
            [
                'nama_usaha' => $data['nama_usaha'],
                'umkm_category_id' => $data['umkm_category_id'],
                'whatsapp' => $data['whatsapp'],
                'alamat' => $data['alamat'],
            ],
            [
                'nama_produk' => $data['nama_produk'],
                'deskripsi' => $data['deskripsi_produk'],
                'harga' => $data['harga'],
            ],
            $request->file('photos', []),
        );

        return redirect()->route('portal.umkm.ajukan')
            ->with('success', 'Pengajuan terkirim! Kami akan memberi tahumu begitu hasil tinjauannya keluar.');
    }
}
