{{-- Dirender dompdf, bukan peramban. Hanya CSS sederhana yang didukung: tanpa
     flexbox, tanpa grid, tanpa gradien. Tata letak memakai blok dan tabel, penempatan
     tepi memakai posisi absolut. --}}
@php
    $navy = '#0e2a59';
    $emas = '#c9932c';
    $emasMuda = '#d9ad4e';
    $redup = '#5b6672';

    /* Ukuran nama DIHITUNG, bukan dipilih dari beberapa tingkat tetap. Nama yang
       pecah dua baris mendorong blok pengesahan sampai menabrak garis kaki, dan
       tangga bertingkat selalu bocor pada panjang yang tidak terduga.

       0.68 adalah rata-rata lebar karakter DejaVu Serif Bold dalam satuan em,
       diukur dari hasil render, bukan dikira-kira. Lebar aman 720pt = lebar A4
       lanskap (842pt) dikurangi margin kiri kanan dan sedikit sisa. */
    $nama = $warga->name;
    $lebarAman = 720;
    $ukuranNama = (int) floor($lebarAman / max(1, mb_strlen($nama) * 0.68));
    $ukuranNama = max(15, min(34, $ukuranNama));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat {{ $certificate->nomor_seri }}</title>
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #1a1a1a;
        }

        /* Bingkai: garis tebal navy di luar, garis tipis emas di dalam. */
        .tepi {
            position: absolute;
            top: 14pt; right: 14pt; bottom: 14pt; left: 14pt;
            border: 3pt solid {{ $navy }};
        }
        .tepi-dalam {
            position: absolute;
            top: 21pt; right: 21pt; bottom: 21pt; left: 21pt;
            border: 0.8pt solid {{ $emas }};
        }

        /* Aksen sudut: potongan garis emas yang menebal di empat sudut. */
        .sudut { position: absolute; width: 42pt; height: 42pt; }
        .sudut-ka { top: 21pt; left: 21pt; border-top: 3pt solid {{ $emas }}; border-left: 3pt solid {{ $emas }}; }
        .sudut-kn { top: 21pt; right: 21pt; border-top: 3pt solid {{ $emas }}; border-right: 3pt solid {{ $emas }}; }
        .sudut-ba { bottom: 21pt; left: 21pt; border-bottom: 3pt solid {{ $emas }}; border-left: 3pt solid {{ $emas }}; }
        .sudut-bn { bottom: 21pt; right: 21pt; border-bottom: 3pt solid {{ $emas }}; border-right: 3pt solid {{ $emas }}; }

        .isi { position: absolute; top: 40pt; left: 52pt; right: 52pt; text-align: center; }

        .lembaga {
            margin-top: 8pt;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 4pt;
            color: {{ $navy }};
        }
        .sub-lembaga {
            margin-top: 3pt;
            font-size: 7.5pt;
            letter-spacing: 2.5pt;
            color: {{ $redup }};
        }

        .judul {
            margin-top: 14pt;
            font-family: "DejaVu Serif", serif;
            font-size: 38pt;
            font-weight: bold;
            letter-spacing: 8pt;
            color: {{ $navy }};
        }
        .garis-judul {
            width: 120pt;
            margin: 7pt auto 0 auto;
            border-top: 2pt solid {{ $emas }};
        }
        .nomor {
            margin-top: 7pt;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8.5pt;
            letter-spacing: 1pt;
            color: {{ $redup }};
        }

        .antar { margin-top: 22pt; font-size: 10pt; color: {{ $redup }}; }

        .nama {
            margin-top: 4pt;
            font-family: "DejaVu Serif", serif;
            font-size: {{ $ukuranNama }}pt;
            font-weight: bold;
            color: {{ $navy }};
        }
        .garis-nama {
            width: 62%;
            margin: 8pt auto 0 auto;
            border-top: 0.8pt solid {{ $emasMuda }};
        }

        .keterangan { margin-top: 14pt; font-size: 10pt; color: {{ $redup }}; }
        .tema {
            margin-top: 6pt;
            font-family: "DejaVu Serif", serif;
            font-size: 17pt;
            font-weight: bold;
            color: {{ $emas }};
        }

        /* Blok pengesahan. TANPA tanda tangan bermaterai atau nama pejabat: sertifikat
           ini diterbitkan sistem, dan mencantumkan tanda tangan yang tidak pernah
           dibubuhkan siapa pun akan menjadikannya dokumen palsu. Yang dinyatakan
           hanyalah lembaga penerbit dan tanggalnya. */
        .sah { margin-top: 24pt; }
        .sah-tanggal { font-size: 9pt; color: {{ $redup }}; }
        .sah-garis {
            width: 150pt;
            margin: 9pt auto 0 auto;
            border-top: 0.8pt solid {{ $navy }};
        }
        .sah-lembaga {
            margin-top: 5pt;
            font-size: 10pt;
            font-weight: bold;
            color: {{ $navy }};
        }
        .sah-peran {
            margin-top: 2pt;
            font-size: 7pt;
            letter-spacing: 1.4pt;
            color: {{ $redup }};
        }

        /* Kaki dipaku ke bawah supaya posisinya tetap walau panjang nama berubah. */
        .kaki {
            position: absolute;
            left: 52pt; right: 52pt; bottom: 42pt;
        }
        .kaki table { width: 100%; border-collapse: collapse; }
        .kaki td { vertical-align: top; font-size: 8pt; color: {{ $redup }}; }
        .kaki .label {
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 1.6pt;
            color: {{ $navy }};
        }
        .kaki .nilai { margin-top: 2pt; font-size: 9pt; color: #1a1a1a; }
        .kaki .tautan {
            margin-top: 2pt;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8pt;
            color: {{ $navy }};
        }
        .pemisah-kaki {
            border-top: 0.8pt solid #d8dde4;
            margin-bottom: 8pt;
        }
        .qr { border: 2pt solid #ffffff; }
        .qr-catatan {
            margin-top: 3pt;
            font-size: 6pt;
            letter-spacing: 0.6pt;
            color: {{ $redup }};
        }
    </style>
</head>
<body>
    <div class="tepi"></div>
    <div class="tepi-dalam"></div>
    <div class="sudut sudut-ka"></div>
    <div class="sudut sudut-kn"></div>
    <div class="sudut sudut-ba"></div>
    <div class="sudut sudut-bn"></div>

    <div class="isi">
        @if($logo !== '')
            <img src="{{ $logo }}" alt="" height="76">
        @endif

        <div class="lembaga">BASAMO NAGARI CREATIVE HUB</div>
        <div class="sub-lembaga">SMART LEARNING CENTER</div>

        <div class="judul">SERTIFIKAT</div>
        <div class="garis-judul"></div>
        <div class="nomor">{{ $certificate->nomor_seri }}</div>

        <div class="antar">Dengan ini menyatakan bahwa</div>
        <div class="nama">{{ $nama }}</div>
        <div class="garis-nama"></div>

        <div class="keterangan">telah mengikuti dan menyelesaikan pelatihan</div>
        <div class="tema">{{ $pelatihan->temaNama() }}</div>

        <div class="sah">
            <div class="sah-tanggal">Diterbitkan pada {{ $certificate->diterbitkan_pada->translatedFormat('j F Y') }}</div>
            <div class="sah-garis"></div>
            <div class="sah-lembaga">Basamo Nagari Creative Hub</div>
            <div class="sah-peran">PENYELENGGARA</div>
        </div>
    </div>

    <div class="kaki">
        <div class="pemisah-kaki"></div>
        <table>
            <tr>
                <td width="42%">
                    @if($warga->nagari)
                        <div class="label">NAGARI</div>
                        {{-- `nama`, bukan `nama_lengkap`: yang terakhir sudah berawalan
                             "Nagari" sehingga mengulang labelnya sendiri. --}}
                        <div class="nilai">{{ $warga->nagari->nama }}</div>
                    @endif
                </td>
                <td width="58%" align="right">
                    {{-- Tabel dalam tabel: dompdf tidak mengenal flexbox, dan ini satu-
                         satunya cara menyandingkan teks dengan QR secara rapi. --}}
                    <table style="width: auto; margin-left: auto;">
                        <tr>
                            <td align="right" style="padding-right: 10pt;">
                                <div class="label">PERIKSA KEASLIAN SERTIFIKAT INI DI</div>
                                <div class="tautan">{{ $urlVerifikasi }}</div>
                                <div class="qr-catatan">ATAU PINDAI KODE DI SAMPING</div>
                            </td>
                            <td width="58" align="right" style="vertical-align: middle;">
                                @if($qr !== '')
                                    <img class="qr" src="{{ $qr }}" alt="" width="54" height="54">
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
