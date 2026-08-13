<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Kode QR alamat verifikasi sertifikat, siap ditanam ke PDF.
 *
 * Dipisahkan dari controller supaya hasilnya dapat benar-benar dipindai ulang di
 * dalam test: kode QR yang salah baru ketahuan setelah sertifikatnya tercetak.
 */
class QrSertifikat
{
    /**
     * Koreksi galat Quartile, bukan Low: sertifikat berakhir di kertas yang terlipat,
     * terkena noda, dan dipindai kamera ponsel di bawah cahaya seadanya.
     */
    public static function dataUri(string $url): string
    {
        $qr = new Builder(
            writer: new PngWriter(),
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Quartile,
            size: 320,
            margin: 0,
        );

        return 'data:image/png;base64,'.base64_encode($qr->build()->getString());
    }
}
