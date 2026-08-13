{{-- Dirender dompdf, bukan peramban: hanya CSS sederhana yang didukung. Hindari
     flexbox, grid, dan properti modern. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat {{ $certificate->nomor_seri }}</title>
    <style>
        @page { margin: 0; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #10222e;
        }
        .lembar {
            width: 100%;
            height: 540pt;
            padding: 38pt 46pt;
            border-top: 10pt solid #003857;
            border-bottom: 10pt solid #c9a227;
        }
        .kepala { text-align: center; }
        .lembaga {
            font-size: 11pt;
            letter-spacing: 3pt;
            text-transform: uppercase;
            color: #52606a;
        }
        .judul {
            margin-top: 10pt;
            font-size: 30pt;
            font-weight: bold;
            letter-spacing: 2pt;
            color: #003857;
        }
        .antar { margin-top: 22pt; text-align: center; font-size: 11pt; color: #52606a; }
        .nama {
            margin-top: 6pt;
            text-align: center;
            font-size: 26pt;
            font-weight: bold;
            border-bottom: 1pt solid #c9a227;
            padding-bottom: 8pt;
        }
        .pelatihan { margin-top: 18pt; text-align: center; font-size: 14pt; }
        .tema { font-weight: bold; font-size: 17pt; color: #003857; }
        .kaki { margin-top: 34pt; width: 100%; font-size: 9.5pt; color: #52606a; }
        .kaki td { vertical-align: top; }
        .seri { font-family: DejaVu Sans Mono, monospace; font-size: 11pt; color: #10222e; }
    </style>
</head>
<body>
<div class="lembar">
    <div class="kepala">
        <div class="lembaga">Basamo Nagari Creative Hub</div>
        <div class="judul">SERTIFIKAT</div>
    </div>

    <div class="antar">Diberikan kepada</div>
    <div class="nama">{{ $warga->name }}</div>

    <div class="pelatihan">
        atas keikutsertaan dan penyelesaian pelatihan<br>
        <span class="tema">{{ $pelatihan->temaNama() }}</span>
    </div>

    <table class="kaki">
        <tr>
            <td width="50%">
                <strong>Nomor Seri</strong><br>
                <span class="seri">{{ $certificate->nomor_seri }}</span><br><br>
                <strong>Tanggal Terbit</strong><br>
                {{ $certificate->diterbitkan_pada->translatedFormat('j F Y') }}
            </td>
            <td width="50%">
                @if($warga->nagari)
                    <strong>Nagari</strong><br>
                    {{ $warga->nagari->nama_lengkap }}<br><br>
                @endif
                <strong>Periksa keasliannya di</strong><br>
                {{ $urlVerifikasi }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>
