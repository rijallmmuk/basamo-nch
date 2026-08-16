<?php

/**
 * Membangun PANDUAN-PENGAJAR.pdf dari PANDUAN-PENGAJAR.md.
 *
 * Jalankan dari akar proyek:  php panduan-pengajar/bangun-pdf.php
 *
 * Markdown dirender memakai league/commonmark yang sudah menjadi dependensi proyek,
 * lalu dicetak lewat Chrome headless. Tidak ada perkakas luar yang perlu dipasang.
 *
 * Gambar ditanam sebagai data URI, bukan dirujuk lewat path. Chrome headless menolak
 * memuat berkas lokal dari halaman yang dibuka dengan file://, dan tanpa penanaman itu
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

// Daftar isi hanya berguna pada tampilan web; di PDF ia menjadi daftar tautan mati.
$markdown = preg_replace('/^## Daftar isi\n.*?\n---\n/ms', '', $markdown, 1);

$environment = new Environment([
    'html_input' => 'allow',
    'allow_unsafe_links' => false,
]);
$environment->addExtension(new CommonMarkCoreExtension);
$environment->addExtension(new TableExtension);

$isi = (new MarkdownConverter($environment))->convert($markdown)->getContent();

// Tanam gambar sebagai data URI.
$isi = preg_replace_callback(
    '/<img src="([^"]+)"([^>]*)>/',
    function (array $cocok) use ($akar): string {
        $path = $akar.'/'.ltrim($cocok[1], '/');

        if (! is_file($path)) {
            fwrite(STDERR, "Gambar tidak ditemukan: {$cocok[1]}\n");
            exit(1);
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return '<img src="data:'.$mime.';base64,'.base64_encode(file_get_contents($path)).'"'.$cocok[2].'>';
    },
    $isi,
);

$gaya = <<<'CSS'
@page { size: A4; margin: 18mm 16mm 20mm 16mm; }
* { box-sizing: border-box; }
body {
    font-family: "DejaVu Sans", "Segoe UI", Arial, sans-serif;
    font-size: 10.5pt; line-height: 1.6; color: #1a1a1a; margin: 0;
}
h1 {
    font-size: 22pt; color: #0e2a59; margin: 0 0 6pt;
    border-bottom: 3px solid #c9932c; padding-bottom: 8pt;
}
h2 {
    font-size: 14pt; color: #0e2a59; margin: 22pt 0 8pt;
    border-left: 4px solid #c9932c; padding-left: 8pt;
    page-break-after: avoid;
}
h3 { font-size: 11.5pt; color: #0e2a59; margin: 14pt 0 5pt; page-break-after: avoid; }
p { margin: 0 0 8pt; }
ul, ol { margin: 0 0 8pt; padding-left: 18pt; }
li { margin-bottom: 3pt; }
strong { color: #0e2a59; }
code {
    font-family: "DejaVu Sans Mono", monospace; font-size: 9pt;
    background: #eef1f5; padding: 1pt 3pt; border-radius: 3px;
}
pre {
    background: #0e2a59; color: #ffffff; padding: 10pt; border-radius: 6px;
    font-size: 9.5pt; overflow-x: auto; page-break-inside: avoid;
}
pre code { background: none; color: inherit; padding: 0; }
table {
    width: 100%; border-collapse: collapse; margin: 0 0 12pt;
    font-size: 9.5pt; page-break-inside: avoid;
}
th {
    background: #0e2a59; color: #ffffff; text-align: left;
    padding: 6pt 8pt; font-weight: bold;
}
td { padding: 6pt 8pt; border-bottom: 1px solid #dde3ea; vertical-align: top; }
tr:nth-child(even) td { background: #f6f8fa; }
img {
    max-width: 100%; height: auto; display: block; margin: 10pt auto;
    border: 1px solid #dde3ea; border-radius: 6px; page-break-inside: avoid;
}
blockquote {
    margin: 0 0 10pt; padding: 8pt 12pt;
    background: #fff8e8; border-left: 4px solid #c9932c;
    page-break-inside: avoid;
}
blockquote p:last-child { margin-bottom: 0; }
hr { border: 0; border-top: 1px solid #dde3ea; margin: 16pt 0; }
a { color: #0e2a59; text-decoration: none; }
CSS;

$html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">'
    .'<title>Panduan Pengajar Smart Learning Center</title>'
    ."<style>{$gaya}</style></head><body>{$isi}</body></html>";

$berkasHtml = sys_get_temp_dir().'/panduan-pengajar-'.getmypid().'.html';
file_put_contents($berkasHtml, $html);

$perintah = sprintf(
    'google-chrome --headless --disable-gpu --no-sandbox --no-pdf-header-footer '
    .'--print-to-pdf=%s %s 2>&1',
    escapeshellarg($keluaran),
    escapeshellarg('file://'.$berkasHtml),
);

exec($perintah, $keluaranPerintah, $kode);
@unlink($berkasHtml);

if ($kode !== 0 || ! is_file($keluaran)) {
    fwrite(STDERR, "Chrome gagal mencetak:\n".implode("\n", $keluaranPerintah)."\n");
    exit(1);
}

printf("PANDUAN-PENGAJAR.pdf dibangun, %.1f MB\n", filesize($keluaran) / 1048576);
