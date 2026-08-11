<?php

namespace App\Support\Auth;

/**
 * Ke mana seseorang dibawa tepat setelah login berhasil.
 *
 * `url` selalu ABSOLUT, sebab tujuan login dapat berpindah host: warga yang
 * menekan Masuk di situs nagari tetangga dipulangkan ke subdomain nagarinya
 * sendiri. URL relatif akan diam-diam menahannya di host yang keliru.
 */
readonly class TujuanLogin
{
    public function __construct(
        public string $url,
        /** Pesan informasi opsional, mis. saat gerbang yang diminta tak terbuka untuk akun ini. */
        public ?string $pesanInfo = null,
    ) {}
}
