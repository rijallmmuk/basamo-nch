<?php

namespace App\Http\Controllers\Public;

use App\Enums\KategoriKontak;
use App\Http\Controllers\Controller;
use App\Models\KontakMasuk;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Section "Hubungi Kami" beranda publik (base URL) — Jadi Mitra / Keluhan & Saran.
 * TANPA email: semua pesan tersimpan di tabel kontak_masuks, dibaca superadmin
 * lewat panel (KontakMasukResource).
 */
class KontakController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kategori' => ['required', Rule::enum(KategoriKontak::class)],
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'no_hp' => ['required', 'string', 'regex:'.PhoneNumber::REGEX],
            // Nagari hanya wajib utk pengajuan kemitraan.
            'nama_nagari' => ['required_if:kategori,mitra', 'nullable', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:5000'],
        ], [
            'nama_nagari.required_if' => 'Nama nagari wajib diisi untuk pengajuan kemitraan.',
        ]);

        $data['no_hp'] = PhoneNumber::normalize($data['no_hp']);

        KontakMasuk::create($data);

        $pesanSukses = match (KategoriKontak::from($data['kategori'])) {
            KategoriKontak::Mitra => 'Pengajuan kemitraan terkirim. Tim SLC Basamo NCH akan menghubungi Anda.',
            KategoriKontak::KeluhanSaran => 'Pesan Anda terkirim. Terima kasih atas masukannya.',
        };

        return redirect(route('public.home').'#kontak')->with('kontak_sukses', $pesanSukses);
    }
}
