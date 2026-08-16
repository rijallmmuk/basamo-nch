<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Panduan penggunaan panel, dibaca dari berkas Markdown yang sama dengan sumber PDF-nya.
 *
 * Satu sumber untuk dua keluaran: PDF yang dibangun {@see panduan-pengajar/bangun-pdf.php}
 * dan halaman panduan di dalam panel. Bila keduanya ditulis terpisah, salah satunya
 * pasti tertinggal setiap kali sistem berubah.
 *
 * Peran lain tinggal ditambahkan ke {@see BERKAS}; sisanya tidak perlu diubah.
 */
class Panduan
{
    /** @var array<string, array{berkas: string, gambar: string, judul: string}> */
    private const BERKAS = [
        'pengajar' => [
            'berkas' => 'PANDUAN-PENGAJAR.md',
            'gambar' => 'panduan-pengajar/gambar',
            'judul' => 'Panduan Pengajar',
        ],
    ];

    /** Peran yang panduannya tersedia bagi pengguna ini, atau null bila belum ada. */
    public static function peranUntuk(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (array_keys(self::BERKAS) as $peran) {
            if ($user->hasRole($peran) && is_file(base_path(self::BERKAS[$peran]['berkas']))) {
                return $peran;
            }
        }

        return null;
    }

    public static function judul(string $peran): string
    {
        return self::BERKAS[$peran]['judul'] ?? 'Panduan';
    }

    /** Isi panduan sebagai HTML siap tampil, lengkap dengan id judul untuk tautan lompat. */
    public static function html(string $peran): string
    {
        $berkas = base_path(self::BERKAS[$peran]['berkas']);

        return Cache::remember(
            'panduan-html:'.$peran.':'.(is_file($berkas) ? filemtime($berkas) : 0),
            now()->addDay(),
            fn (): string => self::render($peran, $berkas),
        );
    }

    /** @return array<int, array{tingkat: int, id: string, teks: string}> */
    public static function daftarIsi(string $peran): array
    {
        preg_match_all(
            '/<h([23]) id="([^"]+)">(.*?)<\/h\1>/s',
            self::html($peran),
            $cocok,
            PREG_SET_ORDER,
        );

        return array_map(fn (array $m): array => [
            'tingkat' => (int) $m[1],
            'id' => $m[2],
            'teks' => trim(strip_tags($m[3])),
        ], $cocok);
    }

    private static function render(string $peran, string $berkas): string
    {
        $markdown = is_file($berkas) ? (string) file_get_contents($berkas) : '';

        // Judul utama dan daftar isi dibuang: keduanya sudah disediakan kerangka halaman.
        $markdown = preg_replace('/\A# .*?\n/', '', $markdown, 1);
        $markdown = preg_replace('/^## Daftar isi\n\n.*?\n\n---\n/ms', '', $markdown, 1);

        $environment = new Environment(['html_input' => 'escape', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        $html = (new MarkdownConverter($environment))->convert($markdown)->getContent();

        // Id judul mengikuti gaya GitHub, sama seperti tautan yang ditulis di Markdown-nya.
        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s', function (array $m): string {
            return '<h'.$m[1].' id="'.self::slug($m[2]).'">'.$m[2].'</h'.$m[1].'>';
        }, $html);

        /* Gambar panduan berada di luar `public/`, sengaja, supaya tidak dapat diambil
           tanpa masuk. Alamatnya dialihkan ke rute panel yang memeriksa kewenangan. */
        $gambar = self::BERKAS[$peran]['gambar'];

        return preg_replace_callback(
            '/<img src="'.preg_quote($gambar, '/').'\/([^"]+)"/',
            fn (array $m): string => '<img loading="lazy" src="'.route('panduan.gambar', [
                'peran' => $peran,
                'berkas' => $m[1],
            ]).'"',
            (string) $html,
        );
    }

    /** Berkas gambar milik panduan peran ini, atau null bila namanya tidak sah. */
    public static function pathGambar(string $peran, string $berkas): ?string
    {
        if (! isset(self::BERKAS[$peran]) || ! preg_match('/^[a-z0-9-]+\.(webp|png|jpg|jpeg)$/', $berkas)) {
            return null;
        }

        $path = base_path(self::BERKAS[$peran]['gambar'].'/'.$berkas);

        return is_file($path) ? $path : null;
    }

    private static function slug(string $teks): string
    {
        $teks = html_entity_decode(strip_tags($teks), ENT_QUOTES, 'UTF-8');
        $teks = mb_strtolower(trim($teks));
        $teks = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $teks);
        $teks = preg_replace('/\s+/u', '-', (string) $teks);

        return trim((string) $teks, '-');
    }
}
