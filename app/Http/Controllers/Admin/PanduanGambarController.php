<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Panduan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Melayani gambar panduan panel.
 *
 * Berkasnya sengaja berada di luar `public/`: panduan memuat tangkapan layar isi
 * panel, dan itu tidak perlu dapat diambil siapa pun tanpa masuk. Kewenangannya
 * mengikuti panduan yang memang tersedia bagi peran pengguna.
 */
class PanduanGambarController extends Controller
{
    public function __invoke(Request $request, string $peran, string $berkas): BinaryFileResponse
    {
        abort_unless(Panduan::peranUntuk($request->user()) === $peran, 404);

        $path = Panduan::pathGambar($peran, $berkas);

        abort_if($path === null, 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
