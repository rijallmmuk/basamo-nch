<?php

/**
 * Membangun PANDUAN-PENGAJAR.pdf dari PANDUAN-PENGAJAR.md.
 *
 * Jalankan dari akar proyek:  php panduan-pengajar/bangun-pdf.php
 *
 * Markdown dirender memakai league/commonmark yang sudah menjadi dependensi proyek,
 * lalu dicetak lewat {@see cetak-pdf.mjs}. Yang perlu dipasang hanya puppeteer-core,
 * dan itu sudah tercatat sebagai devDependency.
 *
 * Gambar ditanam sebagai data URI, bukan dirujuk lewat path. Chrome menolak memuat
 * berkas lokal dari halaman yang dibuka dengan file://, dan tanpa penanaman itu
 * seluruh tangkapan layar hilang dari PDF-nya tanpa peringatan apa pun.
 */

require __DIR__.'/../vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

$akar = dirname(__DIR__);
$sumber = $akar.'/PANDUAN-PENGAJAR.md';
$keluaran = $akar.'/PANDUAN-PENGAJAR.pdf';

$markdown = file_get_contents($sumber);
if ($markdown === false) {
    fwrite(STDERR, "Tidak dapat membaca {$sumber}\n");
    exit(1);
}

/** Berkas gambar menjadi data URI; keluar bila berkasnya hilang, jangan diam-diam kosong. */
$dataUri = function (string $path) use ($akar): string {
    $penuh = $akar.'/'.ltrim($path, '/');

    if (! is_file($penuh)) {
        fwrite(STDERR, "Gambar tidak ditemukan: {$path}\n");
        exit(1);
    }

    $mime = match (strtolower(pathinfo($penuh, PATHINFO_EXTENSION))) {
        'webp' => 'image/webp',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        default => 'application/octet-stream',
    };

    return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($penuh));
};

// Judul utama dan paragraf pembuka pindah ke sampul, jadi dibuang dari badan.
$markdown = preg_replace('/\A# .*?\n\n(.*?)\n\n---\n/s', '', $markdown, 1);

// Daftar isi dirakit ulang sebagai halaman tersendiri di bawah.
preg_match('/^## Daftar isi\n\n(.*?)\n\n---\n/ms', $markdown, $cocokDaftar);
$daftarIsiMd = $cocokDaftar[1] ?? '';
$markdown = preg_replace('/^## Daftar isi\n\n.*?\n\n---\n/ms', '', $markdown, 1);

$environment = new Environment(['html_input' => 'allow', 'allow_unsafe_links' => false]);
$environment->addExtension(new CommonMarkCoreExtension);
$environment->addExtension(new TableExtension);
$converter = new MarkdownConverter($environment);

$isi = $converter->convert($markdown)->getContent();
$daftarIsi = $daftarIsiMd === '' ? '' : $converter->convert($daftarIsiMd)->getContent();

/**
 * Slug judul mengikuti gaya GitHub, sebab tautan di dalam Markdown-nya memang ditulis
 * dengan gaya itu. Tanpa id yang cocok, daftar isi menjadi tautan mati di dalam PDF.
 */
$slug = function (string $teks): string {
    $teks = html_entity_decode(strip_tags($teks), ENT_QUOTES, 'UTF-8');
    $teks = mb_strtolower(trim($teks));
    $teks = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $teks);
    $teks = preg_replace('/\s+/u', '-', $teks);

    return trim((string) $teks, '-');
};

$isi = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s', function (array $m) use ($slug): string {
    return '<h'.$m[1].' id="'.$slug($m[2]).'">'.$m[2].'</h'.$m[1].'>';
}, $isi);

$isi = preg_replace_callback('/<img src="([^"]+)"([^>]*)>/', function (array $m) use ($dataUri): string {
    return '<img src="'.$dataUri($m[1]).'"'.$m[2].'>';
}, $isi);

$logo = $dataUri('public/images/brand/basamo-nch-mark.png');
$tanggal = (new DateTimeImmutable)->format('j') // translatedFormat tak tersedia di luar Laravel
    .' '.[1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
        'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int) date('n')]
    .' '.date('Y');

$gaya = <<<'CSS'
* { box-sizing: border-box; }
body {
    font-family: "Segoe UI", "DejaVu Sans", Arial, sans-serif;
    font-size: 10.5pt; line-height: 1.65; color: #1f2933; margin: 0;
}

/* ── Sampul ───────────────────────────────────────────────────────────── */
.sampul {
    height: 246mm; display: flex; flex-direction: column;
    justify-content: center; align-items: center; text-align: center;
    page-break-after: always; position: relative;
}
.sampul::before {
    content: ""; position: absolute; top: 0; left: 0; right: 0; height: 10mm;
    background: linear-gradient(90deg, #0e2a59 0%, #0e2a59 62%, #c9932c 62%, #c9932c 100%);
}
.sampul img { width: 42mm; height: auto; margin-bottom: 10mm; }
.sampul .lembaga {
    font-size: 10pt; font-weight: 700; letter-spacing: 3pt; color: #0e2a59;
}
.sampul .sub-lembaga {
    font-size: 8pt; letter-spacing: 2pt; color: #5b6672; margin-top: 2mm;
}
.sampul h1 {
    font-size: 30pt; line-height: 1.2; color: #0e2a59; margin: 14mm 0 0;
    border: 0; padding: 0; max-width: 140mm;
}
.sampul .garis { width: 40mm; height: 3px; background: #c9932c; margin: 7mm auto; }
.sampul .ringkas { font-size: 11pt; color: #5b6672; max-width: 125mm; margin: 0 auto; }
.sampul .kaki-sampul {
    position: absolute; bottom: 0; left: 0; right: 0;
    font-size: 9pt; color: #5b6672;
}

/* ── Daftar isi ───────────────────────────────────────────────────────── */
.daftar-isi { page-break-after: always; }
.daftar-isi h2 {
    font-size: 16pt; color: #0e2a59; margin: 0 0 8mm;
    border-left: 4px solid #c9932c; padding-left: 8pt;
}
.daftar-isi ol { list-style: none; counter-reset: bab; padding: 0; margin: 0; }
.daftar-isi li { counter-increment: bab; margin: 0; }
.daftar-isi li a {
    display: block; padding: 7pt 0 7pt 14mm; position: relative;
    border-bottom: 1px solid #e6eaef; color: #1f2933;
    text-decoration: none; font-size: 11pt;
}
.daftar-isi li a::before {
    content: counter(bab); position: absolute; left: 0; width: 10mm;
    text-align: right; color: #c9932c; font-weight: 700;
}

/* ── Badan ────────────────────────────────────────────────────────────── */
h2 {
    font-size: 15pt; color: #0e2a59; margin: 18pt 0 8pt;
    border-left: 4px solid #c9932c; padding-left: 8pt;
    page-break-after: avoid; page-break-before: auto;
}
h3 {
    font-size: 11.5pt; color: #0e2a59; margin: 14pt 0 5pt;
    page-break-after: avoid;
}
p { margin: 0 0 8pt; orphans: 3; widows: 3; }
ul, ol { margin: 0 0 8pt; padding-left: 18pt; }
li { margin-bottom: 3pt; }
strong { color: #0e2a59; }
code {
    font-family: "DejaVu Sans Mono", monospace; font-size: 9pt;
    background: #eef1f5; padding: 1pt 3pt; border-radius: 3px;
}
pre {
    background: #0e2a59; color: #ffffff; padding: 10pt; border-radius: 6px;
    font-size: 9.5pt; page-break-inside: avoid;
}
pre code { background: none; color: inherit; padding: 0; }
table {
    width: 100%; border-collapse: collapse; margin: 0 0 12pt;
    font-size: 9.5pt; page-break-inside: avoid;
}
th {
    background: #0e2a59; color: #ffffff; text-align: left;
    padding: 6pt 8pt; font-weight: 600;
}
td { padding: 6pt 8pt; border-bottom: 1px solid #e6eaef; vertical-align: top; }
tr:nth-child(even) td { background: #f7f9fb; }
img {
    max-width: 100%; height: auto; display: block; margin: 10pt auto;
    border: 1px solid #e6eaef; border-radius: 6px; page-break-inside: avoid;
}
blockquote {
    margin: 0 0 10pt; padding: 8pt 12pt;
    background: #fff8e8; border-left: 4px solid #c9932c;
    page-break-inside: avoid;
}
blockquote p:last-child { margin-bottom: 0; }
hr { border: 0; border-top: 1px solid #e6eaef; margin: 16pt 0; }
a { color: #0e2a59; text-decoration: none; }
CSS;

$sampul = <<<HTML
<div class="sampul">
    <img src="{$logo}" alt="">
    <div class="lembaga">BASAMO NAGARI CREATIVE HUB</div>
    <div class="sub-lembaga">SMART LEARNING CENTER</div>
    <h1>Panduan Pengajar<br>Smart Learning Center</h1>
    <div class="garis"></div>
    <p class="ringkas">Panduan lengkap mengisi pelatihan di Basamo NCH, dari mengenal
    wajah aplikasi sampai menerbitkan sertifikat.</p>
    <div class="kaki-sampul">Diperbarui {$tanggal}</div>
</div>
HTML;

$halamanDaftar = $daftarIsi === '' ? '' :
    '<div class="daftar-isi"><h2>Daftar Isi</h2>'.$daftarIsi.'</div>';

$html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">'
    .'<title>Panduan Pengajar Smart Learning Center</title>'
    ."<style>{$gaya}</style></head><body>{$sampul}{$halamanDaftar}{$isi}</body></html>";

$berkasHtml = sys_get_temp_dir().'/panduan-pengajar-'.getmypid().'.html';
file_put_contents($berkasHtml, $html);

exec(sprintf(
    'node %s %s %s 2>&1',
    escapeshellarg(__DIR__.'/cetak-pdf.mjs'),
    escapeshellarg($berkasHtml),
    escapeshellarg($keluaran),
), $keluaranPerintah, $kode);

@unlink($berkasHtml);

if ($kode !== 0 || ! is_file($keluaran)) {
    fwrite(STDERR, "Gagal mencetak:\n".implode("\n", $keluaranPerintah)."\n");
    exit(1);
}

printf("PANDUAN-PENGAJAR.pdf dibangun, %.1f MB\n", filesize($keluaran) / 1048576);
