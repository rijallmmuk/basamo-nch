<?php

namespace App\Models;

use App\Support\PublicNavigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sertifikat yang sudah terbit atas nama seorang warga untuk satu pelatihan.
 *
 * Isinya tidak pernah diperbarui. Nomor seri dan tanggal terbit adalah janji kepada
 * pihak luar yang memverifikasi, jadi keduanya harus tetap sama sepanjang umur baris.
 */
class Certificate extends Model
{
    protected $fillable = ['pelatihan_id', 'user_id', 'nomor_seri', 'diterbitkan_pada'];

    protected function casts(): array
    {
        return ['diterbitkan_pada' => 'datetime'];
    }

    /**
     * Alamat verifikasi, SELALU di domain utama.
     *
     * Tidak boleh memakai `route()` biasa. Rute publik tidak terikat domain, jadi
     * alamatnya akan mengikuti host tempat warga menekan unduh, dan sertifikat yang
     * diambil dari subdomain nagari akan mencetak alamat subdomain itu selamanya.
     * Berkasnya sudah lepas dari kendali kita begitu diunduh, bahkan bisa dicetak di
     * kertas, sementara subdomain bisa berganti slug atau belum pernah dibuat.
     */
    public static function urlVerifikasiUntuk(string $nomorSeri): string
    {
        return PublicNavigation::indukUrl()
            .route('public.sertifikat.verifikasi', $nomorSeri, absolute: false);
    }

    public function urlVerifikasi(): string
    {
        return static::urlVerifikasiUntuk($this->nomor_seri);
    }

    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
