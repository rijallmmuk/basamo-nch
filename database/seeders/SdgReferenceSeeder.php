<?php

namespace Database\Seeders;

use App\Models\SdgGoal;
use App\Models\SdgIndicator;
use App\Models\SdgPillar;
use App\Models\SdgTarget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Referensi SDGs Desa (Permendesa PDTT 13/2025) — global, idempotent.
 * Struktur: Pilar → Poin (1–18) → Sasaran → Indikator. Dikunci per `kode`/`nomor`
 * agar aman di-seed ulang. Sumber: panduan resmi SDGs Desa, Lampiran I & II
 * (dokumen sumber tidak disertakan di repo).
 *
 * CATATAN: pohon Sasaran/Indikator ditranskripsi LENGKAP 18 poin dari PDF
 * (Lampiran I: poin 1–17 · Lampiran II: poin 18 sub_tema kelembagaan/budaya).
 * Empat kode ganda pada Lampiran I resmi diverifikasi ulang 2026-07-30 dan
 * disambiguasi untuk kebutuhan unique key internal: 13.4→13.4b,
 * 15.1.4→15.1.4b, 15.6→15.6b, serta 17.10(kedua, yang indikatornya sudah
 * bernomor 17.12.1)→17.12. Teks tampilan tetap mengikuti isi sumber.
 */
class SdgReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $pillars = [];
        foreach ($this->pillars() as [$slug, $nama, $warna]) {
            $pillars[$slug] = SdgPillar::updateOrCreate(
                ['slug' => $slug],
                ['nama' => $nama, 'warna' => $warna],
            )->id;
        }

        $tree = $this->tree();
        $metodeMap = $this->metodeMap();
        $targetMap = $this->targetNilaiMap();
        $acuanMap = $this->acuanKuotaMap();

        foreach ($this->goals() as [$nomor, $pillarSlug, $nama, $warna]) {
            $goal = SdgGoal::updateOrCreate(
                ['nomor' => $nomor],
                [
                    'sdg_pillar_id' => $pillars[$pillarSlug],
                    'nama' => $nama,
                    'slug' => Str::slug($nomor.' '.$nama),
                    'warna' => $warna,
                    'ikon' => "img/sdgs/{$nomor}.webp",
                ],
            );

            foreach ($tree[$nomor] ?? [] as [$kodeSasaran, $deskSasaran, $subTema, $indikator]) {
                $target = SdgTarget::updateOrCreate(
                    ['sdg_goal_id' => $goal->id, 'kode' => $kodeSasaran],
                    ['deskripsi' => $deskSasaran, 'sub_tema' => $subTema],
                );

                foreach ($indikator as $ind) {
                    // $ind = [kode, deskripsi, metode?]. Metode: inline (Poin 1–3) atau dari
                    // metodeMap() per nomor Poin (Poin 4–18; keyed per-Poin agar kode 1.x/2.x
                    // pada Poin 18 tak bentrok dengan Poin 1/2).
                    SdgIndicator::updateOrCreate(
                        ['sdg_target_id' => $target->id, 'kode' => $ind[0]],
                        [
                            'deskripsi' => $ind[1],
                            'metode' => $ind[2] ?? ($metodeMap[$nomor][$ind[0]] ?? null),
                            'target_nilai' => $targetMap[$nomor][$ind[0]] ?? null,
                            'satuan_acuan' => $acuanMap[$nomor][$ind[0]] ?? null,
                        ],
                    );
                }
            }
        }
    }

    /** @return array<int, array{0:string,1:string,2:string}> [slug, nama, warna] */
    private function pillars(): array
    {
        return [
            ['sosial', 'Sosial', '#D2264B'],
            ['lingkungan', 'Lingkungan', '#2E9E4F'],
            ['ekonomi', 'Ekonomi', '#E5A200'],
            ['hukum-tata-kelola', 'Hukum & Tata Kelola', '#1F6FB2'],
        ];
    }

    /** @return array<int, array{0:int,1:string,2:string,3:string}> [nomor, pillarSlug, nama, warna] */
    private function goals(): array
    {
        return [
            [1, 'sosial', 'Nagari Tanpa Kemiskinan', '#E5243B'],
            [2, 'sosial', 'Nagari Tanpa Kelaparan', '#DDA63A'],
            [3, 'sosial', 'Nagari Sehat dan Sejahtera', '#4C9F38'],
            [4, 'sosial', 'Pendidikan Nagari Berkualitas', '#C5192D'],
            [5, 'sosial', 'Keterlibatan Perempuan Nagari', '#FF3A21'],
            [6, 'lingkungan', 'Nagari dengan Air Bersih dan Sanitasi Aman', '#26BDE2'],
            [7, 'ekonomi', 'Nagari Berenergi Bersih dan Terbarukan', '#FCC30B'],
            [8, 'ekonomi', 'Pertumbuhan Ekonomi Nagari Merata', '#A21942'],
            [9, 'ekonomi', 'Infrastruktur dan Inovasi Nagari Sesuai Kebutuhan', '#FD6925'],
            [10, 'ekonomi', 'Nagari Tanpa Kesenjangan', '#DD1367'],
            [11, 'lingkungan', 'Kawasan Pemukiman Nagari Aman dan Nyaman', '#FD9D24'],
            [12, 'lingkungan', 'Konsumsi dan Produksi Nagari Sadar Lingkungan', '#BF8B2E'],
            [13, 'lingkungan', 'Nagari Tanggap Perubahan Iklim', '#3F7E44'],
            [14, 'lingkungan', 'Nagari Peduli Lingkungan Laut', '#0A97D9'],
            [15, 'lingkungan', 'Nagari Peduli Lingkungan Darat', '#56C02B'],
            [16, 'hukum-tata-kelola', 'Nagari Damai Berkeadilan', '#00689D'],
            [17, 'ekonomi', 'Kemitraan untuk Pembangunan Nagari', '#19486A'],
            [18, 'hukum-tata-kelola', 'Kelembagaan Nagari Dinamis dan Budaya Nagari Adaptif', '#4C2C92'],
        ];
    }

    /**
     * Ambang "100%" (target_nilai) untuk indikator persen ber-kuota Permendesa.
     * Naik: nilai >= ambang → skor 100. Turun: nilai <= ambang → skor 100.
     * Selain yang terdaftar: naik pakai 100, turun pakai 0.
     *
     * @return array<int, array<string, int>>
     */
    private function targetNilaiMap(): array
    {
        return [
            5 => ['5.7.1' => 30, '5.8.1' => 30, '5.8.2' => 30],   // kuota keterlibatan perempuan min 30%
            8 => ['8.3.1' => 3],                                   // pengangguran terbuka menjadi 3%
            14 => ['14.3.2' => 33],                                // luas kawasan lindung laut min 33% dari luas wilayah Nagari (sasaran 14.3 baris ke-2)
            16 => ['16.17.1' => 30, '16.17.2' => 30, '16.18.1' => 30],
        ];
    }

    /**
     * Acuan penyebut ("dari apa") indikator ber-kuota — ditampilkan di petunjuk
     * form agar ambang tak ambigu (mis. 33% DARI LUAS WILAYAH DESA, bukan dari
     * luas perairan). Sumber: kolom Sasaran Lampiran I Permendesa 13/2025.
     *
     * @return array<int, array<string, string>>
     */
    private function acuanKuotaMap(): array
    {
        return [
            5 => [
                '5.7.1' => 'dari seluruh anggota BPD dan/atau perangkat Nagari',
                '5.8.1' => 'dari seluruh peserta Musyawarah Nagari penyusunan RKPDesa',
                '5.8.2' => 'dari seluruh wakil kelompok masyarakat dalam Musyawarah Nagari',
            ],
            8 => ['8.3.1' => 'dari angkatan kerja Nagari'],
            14 => ['14.3.2' => 'dari luas wilayah Nagari'],
            16 => [
                '16.17.1' => 'dari seluruh anggota BPD',
                '16.17.2' => 'dari seluruh perangkat Nagari',
                '16.18.1' => 'dari seluruh peserta musyawarah Nagari',
            ],
        ];
    }

    /**
     * Metode penilaian per indikator, dikelompokkan per nomor Poin (4–18).
     * pn=persen_naik, pt=persen_turun, bl=boolean, ct=capaian_target.
     * Poin 1–3 memakai metode inline di tree().
     *
     * @return array<int, array<string, string>>
     */
    private function metodeMap(): array
    {
        $pn = 'persen_naik';
        $pt = 'persen_turun';
        $bl = 'boolean';
        $ct = 'capaian_target';

        return [
            4 => [
                '4.1.1' => $pn, '4.2.1' => $pn, '4.2.2' => $pn,
                '4.3.1' => $pt, '4.3.2' => $pt, '4.3.3' => $pt,
                '4.4.1' => $pn, '4.5.1' => $pn, '4.5.2' => $pn, '4.6.1' => $pn, '4.6.2' => $pn,
                '4.7.1' => $ct, '4.7.2' => $ct, '4.7.3' => $ct, '4.7.4' => $ct, '4.7.5' => $ct, '4.7.6' => $ct,
                '4.7.7' => $ct, '4.7.8' => $ct, '4.7.9' => $ct, '4.7.10' => $ct, '4.7.11' => $ct, '4.7.12' => $ct,
                '4.8.1' => $pn, '4.9.1' => $bl, '4.10.1' => $pt,
            ],
            5 => [
                '5.1.1' => $bl, '5.2.1' => $bl, '5.3.1' => $bl,
                '5.4.1' => $ct, '5.4.2' => $ct, '5.4.3' => $ct, '5.4.4' => $ct, '5.4.5' => $ct, '5.4.6' => $ct, '5.4.7' => $bl,
                '5.5.1' => $pt, '5.5.2' => $pt, '5.6.1' => $pt, '5.6.2' => $pt,
                '5.7.1' => $pn, '5.8.1' => $pn, '5.8.2' => $pn, '5.8.3' => $ct, '5.9.1' => $bl,
                '5.10.1' => $pn, '5.10.2' => $pn, '5.11.1' => $pn, '5.12.1' => $ct,
            ],
            6 => [
                '6.1.1' => $pn, '6.2.1' => $pn, '6.3.1' => $pn, '6.4.1' => $pt, '6.5.1' => $bl, '6.5.2' => $pn,
                '6.6.1' => $ct, '6.6.2' => $ct, '6.7.1' => $ct, '6.8.1' => $ct, '6.9.1' => $ct,
            ],
            7 => [
                '7.1.1' => $pn, '7.2.1' => $pt, '7.3.1' => $pn, '7.3.2' => $pn, '7.4.1' => $pn, '7.4.2' => $ct,
            ],
            8 => [
                '8.1.1' => $ct,
                '8.2.1' => $pn, '8.2.2' => $pn, '8.2.3' => $pn, '8.2.4' => $pn, '8.2.5' => $ct, '8.2.6' => $pn,
                '8.2.7' => $pn, '8.2.8' => $pn, '8.2.9' => $pn, '8.2.10' => $bl,
                '8.3.1' => $pt, '8.4.1' => $pt, '8.5.1' => $pt, '8.6.1' => $pn,
                '8.7.1' => $ct, '8.8.1' => $ct, '8.8.2' => $ct, '8.9.1' => $ct,
                '8.10.1' => $ct, '8.10.2' => $ct, '8.10.3' => $ct, '8.10.4' => $bl,
                '8.11.1' => $pn, '8.11.2' => $pn, '8.11.3' => $ct,
            ],
            9 => [
                '9.1.1' => $pn, '9.2.1' => $bl, '9.3.1' => $ct, '9.3.2' => $ct, '9.4.1' => $ct,
                '9.5.1' => $ct, '9.6.1' => $pn, '9.7.1' => $ct, '9.8.1' => $pn, '9.9.1' => $pn,
            ],
            10 => [
                '10.1.1' => $ct, '10.1.2' => $ct, '10.2.1' => $pt, '10.2.2' => $pt, '10.3.1' => $pt,
                '10.4.1' => $pn, '10.5.1' => $pn, '10.5.2' => $pt, '10.6.1' => $ct, '10.7.1' => $pn,
                '10.8.1' => $bl, '10.9.1' => $bl,
            ],
            11 => [
                '11.1.1' => $pn, '11.2.1' => $pn, '11.2.2' => $pn, '11.3.1' => $ct,
                '11.4.1' => $pt, '11.4.2' => $pt, '11.4.3' => $pt, '11.4.4' => $pt, '11.4.5' => $pt, '11.4.6' => $pt,
                '11.5.1' => $pn, '11.5.2' => $pn, '11.5.3' => $pn, '11.5.4' => $pn, '11.6.1' => $bl,
                '11.7.1' => $pt, '11.7.2' => $pt, '11.8.1' => $bl,
                '11.9.1' => $bl, '11.9.2' => $bl, '11.9.3' => $bl, '11.9.4' => $bl,
            ],
            12 => [
                '12.1.1' => $pn, '12.1.2' => $pn, '12.2.1' => $bl, '12.2.2' => $pt, '12.2.3' => $pn,
                '12.3.1' => $pn, '12.3.2' => $ct, '12.4.1' => $bl, '12.4.2' => $pn, '12.5.1' => $ct,
                '12.6.1' => $bl, '12.7.1' => $ct, '12.8.1' => $ct, '12.8.2' => $pn, '12.9.1' => $ct, '12.9.2' => $ct,
            ],
            13 => [
                '13.1.1' => $pt, '13.1.2' => $pt, '13.1.3' => $pt, '13.1.4' => $pt, '13.1.5' => $pt,
                '13.2.1' => $bl, '13.2.2' => $bl, '13.2.3' => $bl, '13.2.4' => $bl, '13.3.1' => $bl, '13.3.2' => $ct,
                '13.4.1' => $bl, '13.4b.1' => $bl, '13.5.1' => $ct, '13.6.1' => $ct, '13.7.1' => $ct,
            ],
            14 => [
                '14.1.1' => $bl, '14.1.2' => $pt, '14.2.1' => $bl, '14.2.2' => $pn,
                '14.3.1' => $pn, '14.3.2' => $pn, '14.4.1' => $bl, '14.4.2' => $pt, // 14.3.2: ambang 33% dari luas wilayah Nagari (targetNilaiMap)
                '14.5.1' => $bl, '14.5.2' => $ct, '14.5.3' => $pn,
            ],
            15 => [
                '15.1.1' => $ct, '15.1.2' => $ct, '15.1.3' => $ct, '15.1.4' => $ct, '15.1.4b' => $ct,
                '15.1.5' => $ct, '15.1.6' => $pn, '15.2.1' => $ct, '15.3.1' => $ct, '15.3.2' => $ct,
                '15.4.1' => $bl, '15.4.2' => $ct, '15.5.1' => $bl, '15.6.1' => $ct, '15.6b.1' => $bl,
                '15.7.1' => $bl, '15.7.2' => $ct, '15.7.3' => $ct,
            ],
            16 => [
                '16.1.1' => $ct, '16.2.1' => $ct, '16.3.1' => $ct, '16.3.2' => $ct, '16.4.1' => $ct, '16.5.1' => $ct,
                '16.6.1' => $bl, '16.6.2' => $ct, '16.6.3' => $ct, '16.7.1' => $bl,
                '16.8.1' => $ct, '16.8.2' => $ct, '16.9.1' => $bl, '16.9.2' => $ct,
                '16.10.1' => $bl, '16.10.2' => $ct, '16.10.3' => $ct, '16.10.4' => $ct, '16.10.5' => $ct, '16.10.6' => $ct,
                '16.11.1' => $pn, '16.11.2' => $pn, '16.12.1' => $bl, '16.12.2' => $ct, '16.12.3' => $ct,
                '16.13.1' => $ct, '16.14.1' => $ct, '16.14.2' => $ct, '16.15.1' => $pn,
                '16.16.1' => $pn, '16.16.2' => $ct, '16.16.3' => $pn, '16.16.4' => $pn, '16.16.5' => $pn,
                '16.17.1' => $pn, '16.17.2' => $pn, '16.18.1' => $pn, '16.18.2' => $pt, '16.19.1' => $pn,
                '16.20.1' => $bl, '16.20.2' => $pn, '16.21.1' => $bl, '16.22.1' => $ct, '16.23.1' => $ct, '16.23.2' => $pn,
            ],
            17 => [
                '17.1.1' => $ct, '17.1.2' => $ct, '17.2.1' => $ct, '17.2.2' => $ct, '17.2.3' => $ct, '17.2.4' => $ct, '17.2.5' => $ct,
                '17.3.1' => $ct, '17.4.1' => $ct, '17.4.2' => $ct, '17.4.3' => $ct, '17.5.1' => $ct, '17.6.1' => $ct,
                '17.7.1' => $pn, '17.8.1' => $pn, '17.9.1' => $ct, '17.10.1' => $bl, '17.11.1' => $ct, '17.11.2' => $ct,
                '17.12.1' => $ct, '17.13.1' => $bl, '17.14.1' => $ct, '17.15.1' => $bl,
                '17.16.1' => $bl, '17.16.2' => $bl, '17.16.3' => $bl,
            ],
            18 => [
                // Kelembagaan (kode 1.x)
                '1.1.1' => $bl, '1.1.2' => $pn, '1.2.1' => $pn, '1.2.2' => $pn, '1.3.1' => $pn, '1.4.1' => $bl,
                '1.5.1' => $pn, '1.5.2' => $pn, '1.5.3' => $pn, '1.5.4' => $pn, '1.5.5' => $pn, '1.5.6' => $pn,
                '1.6.1' => $pn, '1.7.1' => $bl, '1.8.1' => $ct, '1.8.2' => $ct, '1.9.1' => $ct, '1.9.2' => $ct, '1.9.3' => $ct,
                '1.10.1' => $bl, '1.11.1' => $bl, '1.11.2' => $bl, '1.11.3' => $bl, '1.11.4' => $bl,
                '1.11.5' => $pn, '1.11.6' => $pn, '1.11.7' => $pn, '1.12.1' => $bl, '1.12.2' => $bl, '1.12.3' => $bl,
                '1.13.1' => $pn, '1.14.1' => $pn, '1.15.1' => $ct, '1.15.2' => $pn,
                // Budaya (kode 2.x)
                '2.1.1' => $bl, '2.2.1' => $bl, '2.3.1' => $bl, '2.4.1' => $bl,
                '2.5.1' => $pn, '2.5.2' => $pn, '2.5.3' => $pn, '2.5.4' => $pn, '2.5.5' => $pn, '2.5.6' => $pn,
                '2.5.7' => $pt, '2.5.8' => $pn, '2.6.1' => $pn, '2.6.2' => $pn, '2.6.3' => $pn, '2.6.4' => $pn,
                '2.7.1' => $pn, '2.7.2' => $pn, '2.8.1' => $pn, '2.8.2' => $pn, '2.9.1' => $pn, '2.10.1' => $ct,
            ],
        ];
    }

    /**
     * Pohon Sasaran → Indikator per nomor Poin.
     * Format: [kodeSasaran, deskripsiSasaran, subTema|null, [[kodeIndikator, deskripsiIndikator, metode?], ...]].
     * metode = App\Enums\MetodeNilaiIndikator (persen_naik|persen_turun|boolean|capaian_target); null = belum ditetapkan.
     *
     * @return array<int, array<int, array{0:string,1:string,2:?string,3:array<int,array{0:string,1:string}>}>>
     */
    private function tree(): array
    {
        return [
            // ── POIN 6 — Nagari dengan Air Bersih dan Sanitasi Aman (contoh lengkap) ──
            6 => [
                ['6.1', 'Meningkatnya persentase rumah tangga yang memiliki akses air minum aman', null, [
                    ['6.1.1', 'Persentase rumah tangga yang memiliki akses air minum aman'],
                ]],
                ['6.2', 'Meningkatnya persentase rumah tangga yang menempati hunian dengan akses sanitasi aman', null, [
                    ['6.2.1', 'Persentase rumah tangga yang memiliki akses terhadap sanitasi aman'],
                ]],
                ['6.3', 'Meningkatnya rumah tangga yang memiliki fasilitas cuci tangan dengan sabun dan air yang mengalir', null, [
                    ['6.3.1', 'Persentase rumah tangga yang memiliki fasilitas cuci tangan dengan sabun dan air yang mengalir'],
                ]],
                ['6.4', 'Menghentikan praktik buang air besar di tempat terbuka oleh warga Nagari yang melakukan praktik buang air besar di tempat terbuka', null, [
                    ['6.4.1', 'Persentase rumah tangga yang melakukan praktik buang air besar sembarangan di tempat terbuka'],
                ]],
                ['6.5', 'Mengurangi pembuangan limbah cair industri ke lingkungan untuk mencegah pencemaran dan penurunan kualitas air', null, [
                    ['6.5.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelestarian lingkungan di sekitar sumber air dan badan air'],
                    ['6.5.2', 'Persentase jumlah unit usaha yang mengelola limbah cair secara aman'],
                ]],
                ['6.6', 'Tersedianya informasi sumber daya air untuk mengatasi kelangkaan air', null, [
                    ['6.6.1', 'Persentase rumah tangga yang menggunakan air permukaan'],
                    ['6.6.2', 'Persentase rumah tangga yang menggunakan air dalam tanah'],
                ]],
                ['6.7', 'Melindungi sumber daya air yang dimanfaatkan oleh pelaku usaha agar dikelola sesuai prinsip pelindungan lingkungan dan keberlanjutan', null, [
                    ['6.7.1', 'Jumlah sumber daya air yang mengalami penurunan kuantitas ketersediaannya'],
                ]],
                ['6.8', 'Memperkuat penggunaan anggaran pendapatan dan belanja Nagari untuk program dan/atau kegiatan yang berkaitan dengan penyediaan air minum dan sanitasi aman', null, [
                    ['6.8.1', 'Persentase anggaran pendapatan dan belanja Nagari yang dialokasikan pada program dan/atau kegiatan yang berkaitan dengan penyediaan air minum dan sanitasi aman'],
                ]],
                ['6.9', 'Adanya komunitas masyarakat dan/atau lembaga kemasyarakatan di Nagari yang berpartisipasi dalam pengelolaan air minum dan sanitasi aman', null, [
                    ['6.9.1', 'Jumlah komunitas masyarakat dan/atau lembaga kemasyarakatan di Nagari yang berpartisipasi dalam pengelolaan air minum dan sanitasi aman'],
                ]],
            ],

            // ── POIN 1 — Nagari Tanpa Kemiskinan ──
            1 => [
                ['1.1', 'Tidak ada warga Nagari yang berstatus miskin', null, [
                    ['1.1.1', 'Persentase warga Nagari miskin ekstrem di Nagari', 'persen_turun'],
                    ['1.1.2', 'Persentase keluarga miskin ekstrem di Nagari', 'persen_turun'],
                    ['1.1.3', 'Persentase warga Nagari yang hidup di bawah garis kemiskinan daerah kabupaten/kota', 'persen_turun'],
                    ['1.1.4', 'Persentase keluarga yang hidup di bawah garis kemiskinan daerah kabupaten/kota', 'persen_turun'],
                ]],
                ['1.2', 'Persentase keluarga miskin di Nagari penerima bantuan sosial mencapai 100%', null, [
                    ['1.2.1', 'Persentase keluarga miskin penerima bantuan sosial', 'persen_naik'],
                ]],
                ['1.3', 'Persentase warga Nagari miskin dan rentan peserta SJSN Bidang Kesehatan mencapai 100%', null, [
                    ['1.3.1', 'Persentase warga Nagari miskin dan rentan peserta SJSN bidang kesehatan', 'persen_naik'],
                ]],
                ['1.4', 'Meningkatnya warga Nagari miskin dan rentan peserta SJSN Bidang Ketenagakerjaan mencapai 100%', null, [
                    ['1.4.1', 'Persentase warga Nagari miskin dan rentan yang bekerja menjadi peserta SJSN bidang ketenagakerjaan', 'persen_naik'],
                ]],
                ['1.5', 'Persentase rumah tangga miskin mendapat layanan dasar berupa akses terhadap layanan air minum aman mencapai 100%', null, [
                    ['1.5.1', 'Persentase rumah tangga miskin yang mendapat akses pada layanan air minum aman', 'persen_naik'],
                    ['1.5.2', 'Persentase rumah tangga miskin yang mendapat akses pada mobilitas dasar', 'persen_naik'],
                    ['1.5.3', 'Persentase rumah tangga miskin yang mendapat akses pada fasilitas penyehatan dasar', 'persen_naik'],
                ]],
                ['1.6', 'Persentase rumah tangga miskin mendapat layanan dasar berupa akses terhadap sanitasi layak mencapai 100%', null, [
                    ['1.6.1', 'Persentase rumah tangga miskin yang mendapat akses pada layanan sanitasi aman', 'persen_naik'],
                ]],
                ['1.7', 'Meningkatnya persentase warga dewasa, menurut jenis kelamin, yang memiliki hak atas tanah dan/atau rumah', null, [
                    ['1.7.1', 'Persentase warga dewasa, menurut jenis kelamin, yang memiliki hak atas tanah dan/atau rumah', 'persen_naik'],
                ]],
                ['1.8', 'Menurunnya proporsi warga Nagari yang terdampak bencana', null, [
                    ['1.8.1', 'Persentase rumah tangga yang mengalami kerugian materiil akibat bencana dalam 6 bulan terakhir', 'persen_turun'],
                    ['1.8.2', 'Persentase warga Nagari yang mengungsi akibat bencana dalam 6 bulan terakhir', 'persen_turun'],
                    ['1.8.3', 'Persentase warga Nagari yang meninggal akibat terdampak bencana dalam 6 bulan terakhir', 'persen_turun'],
                    ['1.8.4', 'Persentase warga Nagari yang hilang akibat terdampak bencana dalam 6 bulan terakhir', 'persen_turun'],
                    ['1.8.5', 'Persentase warga Nagari yang terluka/sakit akibat terdampak bencana dalam 6 bulan terakhir', 'persen_turun'],
                ]],
                ['1.9', 'Tersedianya dokumen Nagari berupa peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai perubahan iklim dan penanggulangan bencana', null, [
                    ['1.9.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai mitigasi dan adaptasi perubahan iklim di Nagari', 'boolean'],
                    ['1.9.2', 'Tersedianya Peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai penanggulangan bencana alam, bencana non-alam, dan bencana sosial di Nagari', 'boolean'],
                ]],
                ['1.10', 'Meningkatnya proporsi anggaran Nagari untuk program/kegiatan pengentasan kemiskinan', null, [
                    ['1.10.1', 'Proporsi anggaran Nagari untuk pengentasan kemiskinan', 'capaian_target'],
                ]],
                ['1.11', 'Meningkatnya proporsi anggaran Nagari untuk pengentasan kemiskinan melalui bantuan sosial dan ekonomi produktif', null, [
                    ['1.11.1', 'Proporsi anggaran Nagari untuk pengentasan kemiskinan melalui ekonomi produktif', 'capaian_target'],
                ]],
                ['1.12', 'Meningkatnya proporsi anggaran Nagari untuk pengentasan kemiskinan melalui bantuan pendidikan, kesehatan, dan pelindungan sosial', null, [
                    ['1.12.1', 'Proporsi anggaran Nagari untuk pengentasan kemiskinan melalui bantuan pendidikan, kesehatan, dan pelindungan sosial', 'capaian_target'],
                ]],
            ],

            // ── POIN 2 — Nagari Tanpa Kelaparan ──
            2 => [
                ['2.1', 'Tidak ada warga Nagari yang kelaparan', null, [
                    ['2.1.1', 'Persentase warga Nagari yang mengonsumsi makanan yang sehat dan bergizi kurang dari 2 (dua) kali dalam sehari', 'persen_turun'],
                ]],
                ['2.2', 'Prevalensi anak stunting (pendek dan sangat pendek) mencapai 0%', null, [
                    ['2.2.1', 'Persentase anak stunting (pendek dan sangat pendek), usia 0-23 bulan', 'persen_turun'],
                ]],
                ['2.3', 'Prevalensi anemia pada ibu hamil usia 19-49 tahun mencapai 0%', null, [
                    ['2.3.1', 'Persentase ibu hamil usia 19-49 tahun yang mengalami anemia', 'persen_turun'],
                ]],
                ['2.4', 'Persentase bayi mendapat ASI eksklusif mencapai 100%', null, [
                    ['2.4.1', 'Persentase bayi mendapat ASI eksklusif', 'persen_naik'],
                ]],
                ['2.5', 'Prevalensi anak balita wasting (berat badan/tinggi badan) mencapai 0%', null, [
                    ['2.5.1', 'Persentase anak usia di bawah 5 tahun yang wasting (berat badan/tinggi badan)', 'persen_turun'],
                ]],
                ['2.6', 'Meningkatnya pendapatan petani skala kecil (petani miskin) dan buruh tani (petani tanpa lahan)', null, [
                    ['2.6.1', 'Rata-rata pendapatan petani miskin dan buruh tani dalam jangka waktu 1 tahun terakhir', 'capaian_target'],
                ]],
                ['2.7', 'Tersedianya 100% luas lahan pertanian pangan ditetapkan sebagai kawasan pertanian pangan berkelanjutan', null, [
                    ['2.7.1', 'Persentase luas lahan pertanian pangan di Nagari ditetapkan sebagai kawasan pertanian pangan berkelanjutan', 'persen_naik'],
                ]],
            ],

            // ── POIN 3 — Nagari Sehat dan Sejahtera ──
            3 => [
                ['3.1', 'Menurunnya angka kematian ibu di Nagari mencapai 0', null, [
                    ['3.1.1', 'Jumlah kematian ibu di nagari yang berkaitan dengan kehamilan, persalinan, dan masa nifas dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.2', 'Persentase persalinan di fasilitas pelayanan kesehatan mencapai 100%', null, [
                    ['3.2.1', 'Persentase ibu hamil yang melakukan persalinan di fasilitas pelayanan kesehatan', 'persen_naik'],
                ]],
                ['3.3', 'Persentase persalinan menggunakan tenaga kesehatan mencapai 100%', null, [
                    ['3.3.1', 'Persentase ibu hamil yang melakukan persalinan ditolong oleh tenaga kesehatan terlatih', 'persen_naik'],
                ]],
                ['3.4', 'Menurunnya kasus bayi yang meninggal dunia sebelum melewati ulang tahun pertama dan/atau ke-lima mencapai 0', null, [
                    ['3.4.1', 'Jumlah kasus bayi yang meninggal dunia sebelum ulang tahun pertama (AKB)', 'capaian_target'],
                    ['3.4.2', 'Jumlah kasus anak berusia 0-59 bulan yang meninggal dunia sebelum ulang tahun ke-lima (AKBa)', 'capaian_target'],
                ]],
                ['3.5', 'Menurunnya kasus bayi yang meninggal dunia dalam periode 28 hari pertama kehidupan', null, [
                    ['3.5.1', 'Jumlah kasus bayi yang meninggal dunia dalam periode 28 hari pertama kehidupan (Angka Kematian Neonatal/AKN)', 'capaian_target'],
                ]],
                ['3.6', 'Jumlah warga Nagari yang baru terinfeksi HIV mencapai 0', null, [
                    ['3.6.1', 'Jumlah warga Nagari yang baru terinfeksi HIV dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.7', 'Seluruh warga Nagari pengidap HIV mendapatkan terapi antiretroviral (ARV) dan mengikuti pengobatan secara berkelanjutan', null, [
                    ['3.7.1', 'Persentase warga Nagari pengidap HIV mendapatkan terapi antiretroviral (ARV) dan mengikuti pengobatan secara berkelanjutan dalam 1 tahun terakhir', 'persen_naik'],
                ]],
                ['3.8', 'Jumlah warga Nagari terinfeksi Tuberkulosis (TB) mencapai 0', null, [
                    ['3.8.1', 'Jumlah kasus warga Nagari yang terinfeksi Tuberkulosis (TB) dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.9', 'Seluruh jumlah warga Nagari penderita Tuberkulosis (TB) mendapatkan pengobatan sampai dinyatakan sembuh', null, [
                    ['3.9.1', 'Jumlah warga Nagari penderita Tuberkulosis (TB) yang mendapatkan pengobatan sampai dinyatakan sembuh dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.10', 'Jumlah warga Nagari terkena malaria mencapai 0', null, [
                    ['3.10.1', 'Jumlah kasus warga Nagari yang terkena malaria dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.11', 'Meningkatnya jumlah warga Nagari yang terkena malaria dan mendapatkan pengobatan tepat waktu', null, [
                    ['3.11.1', 'Jumlah warga Nagari yang terkena malaria dan mendapatkan pengobatan tepat waktu dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.12', 'Menurunnya kasus warga Nagari terinfeksi Hepatitis B mencapai 0', null, [
                    ['3.12.1', 'Jumlah kasus warga Nagari yang terinfeksi Hepatitis B dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.13', 'Seluruh warga Nagari yang terinfeksi Hepatitis B mendapatkan pengobatan dan rutin meminum obat seumur hidup', null, [
                    ['3.13.1', 'Persentase warga Nagari yang terinfeksi hepatitis B mendapatkan pengobatan dan rutin meminum obat seumur hidup dalam 1 tahun terakhir', 'persen_naik'],
                ]],
                ['3.14', 'Seluruh warga Nagari yang terinfeksi kusta mendapatkan pengobatan dan rutin meminum obat sampai dinyatakan sembuh', null, [
                    ['3.14.1', 'Jumlah warga Nagari yang terinfeksi kusta dalam 1 tahun terakhir', 'capaian_target'],
                    ['3.14.2', 'Jumlah warga Nagari yang terinfeksi kusta mendapatkan pengobatan dan rutin meminum obat sampai dinyatakan sembuh dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.15', 'Seluruh warga Nagari yang terinfeksi Filariasis (Kaki Gajah)', null, [
                    ['3.15.1', 'Jumlah warga Nagari yang terinfeksi Filariasis dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.16', 'Menurunnya jumlah warga Nagari penderita kardiovaskuler, kanker, diabetes atau penyakit pernapasan kronis mencapai 0', null, [
                    ['3.16.1', 'Jumlah warga Nagari usia 30-70 tahun penderita penyakit kardiovaskuler dalam 1 tahun terakhir', 'capaian_target'],
                    ['3.16.2', 'Jumlah warga Nagari usia 30-70 tahun penderita penyakit kanker dalam 1 tahun terakhir', 'capaian_target'],
                    ['3.16.3', 'Jumlah warga Nagari usia 30-70 tahun penderita penyakit diabetes dalam 1 tahun terakhir', 'capaian_target'],
                    ['3.16.4', 'Jumlah warga Nagari usia 30-70 tahun penderita penyakit pernapasan kronis dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.17', 'Menurunnya jumlah anak berusia dibawah 18 tahun yang merokok', null, [
                    ['3.17.1', 'Jumlah anak berusia 10-18 tahun merokok dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.18', 'Prevalensi warga Nagari terkena tekanan darah tinggi mencapai 0%', null, [
                    ['3.18.1', 'Persentase warga Nagari penderita tekanan darah tinggi dalam 1 tahun terakhir', 'persen_turun'],
                ]],
                ['3.19', 'Persentase warga Nagari penderita tekanan darah tinggi mendapatkan pengobatan secara rutin mencapai 100%', null, [
                    ['3.19.1', 'Persentase warga Nagari penderita tekanan darah tinggi mendapatkan pengobatan rutin dalam 1 tahun terakhir', 'persen_naik'],
                ]],
                ['3.20', 'Persentase warga Nagari berusia di atas 18 tahun yang mengalami obesitas adalah 0%', null, [
                    ['3.20.1', 'Persentase warga Nagari berusia di atas 18 tahun yang mengalami obesitas dalam 1 tahun terakhir', 'persen_turun'],
                ]],
                ['3.21', 'Prevalensi warga Nagari yang menyalahgunakan NAPZA (Narkotika, Psikotropika, dan Zat Adiktif Lainnya) mencapai 0%', null, [
                    ['3.21.1', 'Persentase warga Nagari yang menyalahgunakan NAPZA dalam 1 tahun terakhir', 'persen_turun'],
                ]],
                ['3.22', 'Persentase korban penyalahgunaan NAPZA di Nagari yang ditangani panti rehabilitasi sosial mencapai 100%', null, [
                    ['3.22.1', 'Persentase korban penyalahgunaan NAPZA di Nagari yang ditangani panti rehabilitasi sosial dalam 1 tahun terakhir', 'persen_naik'],
                ]],
                ['3.23', 'Persentase pasangan usia reproduksi (19-49) yang mengikuti program Keluarga Berencana (KB) mencapai 100%', null, [
                    ['3.23.1', 'Persentase pasangan usia reproduksi (19-49) yang mengikuti program KB dalam 1 tahun terakhir', 'persen_naik'],
                ]],
                ['3.24', 'Menurunnya angka kelahiran pada remaja usia dibawah 19 tahun (age specific fertility rate/ASFR) mencapai 0%', null, [
                    ['3.24.1', 'Jumlah kelahiran pada remaja usia 10-19 tahun dalam 1 tahun terakhir', 'capaian_target'],
                ]],
                ['3.25', 'Meningkatnya jumlah keluarga cukup/paling banyak memiliki 2 (dua) anak', null, [
                    ['3.25.1', 'Jumlah rata-rata anak yang dilahirkan oleh perempuan usia 19-49 tahunan', 'capaian_target'],
                ]],
                ['3.26', 'Menurunnya proporsi warga Nagari yang memiliki keluhan kesehatan dan terganggu aktifitasnya namun tidak berobat jalan', null, [
                    ['3.26.1', 'Persentase warga Nagari yang memiliki keluhan kesehatan dan terganggu aktifitasnya namun tidak berobat jalan', 'persen_turun'],
                ]],
                ['3.27', 'Persentase warga Nagari peserta SJSN Bidang Kesehatan mencapai 100%', null, [
                    ['3.27.1', 'Persentase warga Nagari peserta SJSN bidang kesehatan', 'persen_naik'],
                ]],
                ['3.28', 'Persentase Imunisasi dasar lengkap pada bayi mencapai 100%', null, [
                    ['3.28.1', 'Persentase bayi usia 12-23 bulan yang mendapat imunisasi dasar lengkap', 'persen_naik'],
                ]],
            ],

            // ── POIN 4 — Pendidikan Nagari Berkualitas ──
            4 => [
                ['4.1', 'Persentase anak usia sekolah yang mengikuti program wajib belajar mencapai 100%', null, [
                    ['4.1.1', 'Tingkat penyelesaian pendidikan jenjang SD/sederajat'],
                ]],
                // 4.2: sel Sasaran kosong di PDF — deskripsi disimpulkan dari indikatornya.
                ['4.2', 'Meningkatnya tingkat penyelesaian pendidikan jenjang SMP dan SMA/sederajat', null, [
                    ['4.2.1', 'Tingkat penyelesaian pendidikan jenjang SMP/sederajat'],
                    ['4.2.2', 'Tingkat penyelesaian pendidikan jenjang SMA/sederajat'],
                ]],
                ['4.3', 'Menurunnya persentase anak tidak sekolah pada usia sekolah sesuai dengan jenjang wajib belajar 12 tahun', null, [
                    ['4.3.1', 'Persentase anak tidak sekolah usia 7-12 tahun untuk jenjang SD/sederajat'],
                    ['4.3.2', 'Persentase anak tidak sekolah usia 13-15 tahun untuk jenjang SMP/sederajat'],
                    ['4.3.3', 'Persentase anak tidak sekolah usia 16-18 tahun untuk jenjang SMA/sederajat'],
                ]],
                ['4.4', 'Meningkatnya persentase anak usia 3-6 tahun yang mengikuti PAUD HI/sederajat, menurut jenis kelamin', null, [
                    ['4.4.1', 'Persentase anak usia 3-6 tahun yang mengikuti PAUD HI/sederajat, menurut jenis kelamin'],
                ]],
                ['4.5', 'Meningkatnya persentase remaja (15-24 tahun) dan dewasa (25-64 tahun) dalam pendidikan serta pelatihan formal dan non formal, menurut jenis kelamin', null, [
                    ['4.5.1', 'Persentase remaja (15-24 tahun) dalam pendidikan serta pelatihan formal dan non formal dalam 1 tahun terakhir, menurut jenis kelamin'],
                    ['4.5.2', 'Persentase dewasa (25-64 tahun) dalam pendidikan serta pelatihan formal dan non formal dalam 1 tahun terakhir, menurut jenis kelamin'],
                ]],
                ['4.6', 'Meningkatnya persentase remaja (10-18 tahun) dan dewasa (19-59 tahun) dengan keterampilan teknologi informasi dan komunikasi (TIK)', null, [
                    ['4.6.1', 'Persentase remaja (10-18 tahun) dan dewasa (19-59 tahun) dengan keterampilan teknologi informasi dan komunikasi (TIK)'],
                    ['4.6.2', 'Persentase remaja (10-18 tahun) dan dewasa (19-59 tahun) yang memiliki akses terhadap internet'],
                ]],
                ['4.7', 'Meningkatnya Rasio Angka Partisipasi Murni (APM) dan Rasio Angka Partisipasi Kasar (APK)', null, [
                    ['4.7.1', 'Rasio APM pada tingkat SD/sederajat antara perempuan dan laki-laki'],
                    ['4.7.2', 'Rasio APM pada tingkat SMP/sederajat antara perempuan dan laki-laki'],
                    ['4.7.3', 'Rasio APM pada tingkat SMA/sederajat antara perempuan dan laki-laki'],
                    ['4.7.4', 'Rasio APM pada tingkat SD/sederajat antara disabilitas/non-disabilitas'],
                    ['4.7.5', 'Rasio APM pada tingkat SMP/sederajat antara disabilitas/non-disabilitas'],
                    ['4.7.6', 'Rasio APM pada tingkat SMA/sederajat antara disabilitas/non-disabilitas'],
                    ['4.7.7', 'Rasio APK pada tingkat SD/sederajat antara perempuan dan laki-laki'],
                    ['4.7.8', 'Rasio APK pada tingkat SMP/sederajat antara perempuan dan laki-laki'],
                    ['4.7.9', 'Rasio APK pada tingkat SMA/sederajat antara perempuan dan laki-laki'],
                    ['4.7.10', 'Rasio APK pada tingkat SD/sederajat antara disabilitas/non-disabilitas'],
                    ['4.7.11', 'Rasio APK pada tingkat SMP/sederajat antara disabilitas/non-disabilitas'],
                    ['4.7.12', 'Rasio APK pada tingkat SMA/sederajat antara disabilitas/non-disabilitas'],
                ]],
                ['4.8', 'Meningkatnya persentase angka melek aksara anak umur >=15 tahun', null, [
                    ['4.8.1', 'Persentase warga Nagari usia >=15 tahun yang memiliki kemampuan membaca dan menulis untuk berkomunikasi dengan orang lain'],
                ]],
                ['4.9', 'Tersedia Taman Bacaan Masyarakat atau perpustakaan', null, [
                    ['4.9.1', 'Tersedia Taman Bacaan Masyarakat atau perpustakaan'],
                ]],
                ['4.10', 'Menurunnya persentase siswa yang mengalami perundungan', null, [
                    ['4.10.1', 'Persentase anak usia sekolah sesuai tingkat jenjang pendidikan yang mengalami perundungan dalam 1 tahun terakhir'],
                ]],
            ],

            // ── POIN 5 — Keterlibatan Perempuan Nagari ──
            5 => [
                ['5.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai kesetaraan gender dan penghapusan diskriminasi berdasarkan jenis kelamin', null, [
                    ['5.1.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai kesetaraan gender dan penghapusan diskriminasi berdasarkan jenis kelamin'],
                ]],
                ['5.2', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai advokasi pekerja migran perempuan', null, [
                    ['5.2.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan pekerja migran perempuan'],
                ]],
                ['5.3', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan perempuan dan tindak pidana perdagangan orang', null, [
                    ['5.3.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan perempuan dan tindak pidana perdagangan orang'],
                ]],
                ['5.4', 'Menurunnya jumlah kasus kekerasan (fisik, seksual, dan/atau perundungan) terhadap perempuan mencapai 0', null, [
                    ['5.4.1', 'Jumlah kasus kekerasan (fisik, seksual, dan/atau perundungan) dalam 1 tahun terakhir yang dilakukan oleh pasangan atau mantan pasangan terhadap perempuan usia 19-64 tahun, yang sudah pernah menikah'],
                    ['5.4.2', 'Jumlah perempuan usia 19-64 tahun, yang sudah pernah menikah dalam 1 tahun terakhir mengalami kekerasan (fisik, seksual, dan/atau perundungan) oleh pasangan atau mantan pasangan'],
                    ['5.4.3', 'Jumlah kasus kekerasan (fisik, seksual, dan/atau perundungan) dalam 1 tahun terakhir yang dilakukan oleh orang lain terhadap perempuan usia di bawah 19 tahun'],
                    ['5.4.4', 'Jumlah perempuan usia di bawah 19 tahun dalam 1 tahun terakhir mengalami kasus kekerasan (fisik, seksual, dan/atau perundungan) yang dilakukan oleh orang lain'],
                    ['5.4.5', 'Jumlah kasus kekerasan (fisik, seksual, dan/atau perundungan) dalam 1 tahun terakhir yang dilakukan oleh orang lain terhadap perempuan usia 19-64 tahun'],
                    ['5.4.6', 'Jumlah perempuan usia 19-64 tahun dalam 1 tahun terakhir mengalami kasus kekerasan (fisik, seksual, dan/atau perundungan) yang dilakukan oleh orang lain'],
                    ['5.4.7', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pencegahan dan penanganan kekerasan terhadap perempuan'],
                ]],
                ['5.5', 'Menurunnya persentase perempuan umur 20-24 tahun yang menikah sebelum 18 tahun', null, [
                    ['5.5.1', 'Persentase perempuan umur 20-24 tahun yang usia kawin pertama atau usia hidup bersama pertama sebelum umur 15 tahun'],
                    ['5.5.2', 'Persentase perempuan umur 20-24 tahun yang usia kawin pertama atau usia hidup bersama pertama sebelum umur 18 tahun'],
                ]],
                ['5.6', 'Menurunnya persentase perkawinan resmi/tidak resmi pada perempuan yang berusia di bawah 19 tahun mencapai 0%', null, [
                    ['5.6.1', 'Persentase Perkawinan resmi (tercatat di instansi pemerintah yang berwenang) atau tidak resmi pada perempuan yang berusia di bawah 15 tahun dalam 1 tahun terakhir'],
                    ['5.6.2', 'Persentase Perkawinan resmi (tercatat di instansi pemerintah yang berwenang) atau tidak resmi pada perempuan yang berusia di bawah 18 tahun dalam 1 tahun terakhir'],
                ]],
                ['5.7', 'Meningkatnya persentase perempuan menjadi anggota BPD dan/atau perangkat Nagari minimal 30%', null, [
                    ['5.7.1', 'Persentase perempuan yang menjadi anggota BPD dan/atau perangkat Nagari'],
                ]],
                ['5.8', 'Meningkatnya persentase perempuan yang menghadiri Musyawarah Nagari, memiliki hak suara dalam musdes dan berpartisipasi dalam Pembangunan Nagari minimal 30%', null, [
                    ['5.8.1', 'Persentase perempuan yang menghadiri Musyawarah Nagari untuk penyusunan RKPDesa dalam 1 tahun terakhir'],
                    ['5.8.2', 'Persentase perempuan yang mewakili kelompok masyarakat dalam Musyawarah Nagari untuk penyusunan RKPDes dalam 1 tahun terakhir'],
                    ['5.8.3', 'Jumlah usulan program dan/atau kegiatan Pembangunan Nagari dari perempuan dan/atau kelompok perempuan yang masuk ke dalam dokumen RKPDesa dan APBDesa'],
                ]],
                ['5.9', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan bagi perempuan untuk mendapatkan informasi, pendidikan, dan pelayanan terkait program keluarga berencana, serta kesehatan seksual dan reproduksi', null, [
                    ['5.9.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan bagi perempuan untuk mendapatkan informasi, pendidikan, dan pelayanan terkait program keluarga berencana, serta kesehatan seksual dan reproduksi'],
                ]],
                ['5.10', 'Meningkatnya akses warga Nagari yang bekerja sebagai petani terhadap hak kepemilikan atas tanah pertanian', null, [
                    ['5.10.1', 'Persentase warga Nagari yang bekerja sebagai petani yang memiliki hak atas tanah pertanian'],
                    ['5.10.2', 'Persentase warga Nagari perempuan yang bekerja sebagai petani yang memiliki hak atas tanah pertanian berdasarkan jenis hak atas tanah kepemilikan'],
                ]],
                ['5.11', 'Meningkatnya jumlah persentase warga Nagari yang berusia di atas 18 tahun memiliki alat komunikasi berupa telepon genggam', null, [
                    ['5.11.1', 'Proporsi Persentase warga Nagari yang berusia di atas 18 tahun memiliki alat komunikasi berupa telepon genggam'],
                ]],
                ['5.12', 'Meningkatnya alokasi Keuangan Nagari untuk program dan/atau kegiatan pemberdayaan perempuan', null, [
                    ['5.12.1', 'Persentase Keuangan Nagari yang dialokasikan untuk program dan/atau kegiatan pemberdayaan perempuan'],
                ]],
            ],

            // ── POIN 7 — Nagari Berenergi Bersih dan Terbarukan ──
            7 => [
                ['7.1', 'Persentase rumah tangga pengguna listrik mencapai 100%', null, [
                    ['7.1.1', 'Persentase Rumah Tangga yang sumber peneranganya menggunakan listrik'],
                ]],
                ['7.2', 'Menurunnya persentase rumah tangga yang menggunakan daya listrik lebih kecil atau sama dengan 900 VA', null, [
                    ['7.2.1', 'Persentase rumah tangga yang menggunakan daya listrik lebih kecil atau sama dengan 900 VA'],
                ]],
                ['7.3', 'Meningkatnya persentase rumah tangga pengguna gas bumi, biogas, listrik, atau energi terbarukan lainnya untuk memasak', null, [
                    ['7.3.1', 'Proporsi Persentase rumah tangga yang menggunakan gas (Gas kota, LPG, biogas) sebagai bahan bakar utama untuk memasak'],
                    ['7.3.2', 'Proporsi Persentase rumah tangga yang menggunakan listrik sebagai bahan bakar utama untuk memasak'],
                ]],
                ['7.4', 'Meningkatnya akses rumah tangga terhadap energi terbarukan', null, [
                    ['7.4.1', 'Presentase rumah tangga yang memiliki akses terhadap energi terbarukan'],
                    ['7.4.2', 'Jumlah unit pembangkit listrik dan/atau sumber energi terbarukan yang terpasang dan berfungsi di Nagari'],
                ]],
            ],

            // ── POIN 8 — Pertumbuhan Ekonomi Nagari Merata ──
            8 => [
                ['8.1', 'Meningkatnya pendapatan rumah tangga diatas garis kemiskinan di tingkat kabupaten/kota', null, [
                    ['8.1.1', 'Rata-rata pendapatan per tahun warga Nagari yang bekerja per tahun'],
                ]],
                ['8.2', 'Meningkatnya akses bagi pekerja sektor informal, UMKM, koperasi, dan badan usaha milik Nagari, terhadap layanan keuangan formal', null, [
                    ['8.2.1', 'Persentase pekerja sektor informal yang memiliki akses terhadap layanan keuangan formal'],
                    ['8.2.2', 'Persentase badan usaha milik Nagari yang memiliki akses terhadap layanan keuangan formal'],
                    ['8.2.3', 'Persentase usaha mikro, kecil, dan menengah yang memiliki akses terhadap layanan keuangan formal'],
                    ['8.2.4', 'Persentase pekerja koperasi yang memiliki akses terhadap layanan keuangan informal'],
                    ['8.2.5', 'Proporsi Persentase modal BUM Nagari yang bersumber dari kredit keuangan formal'],
                    ['8.2.6', 'Proporsi Persentase UMKM dan koperasi di nagari yang memiliki akses terhadap layanan keuangan formal'],
                    ['8.2.7', 'Persentase warga nagari yang memiliki rekening tabungan di bank dan/atau lembaga keuangan formal lainnya'],
                    ['8.2.8', 'Persentase warga nagari yang mengakses kredit dari layanan keuangan formal'],
                    ['8.2.9', 'Persentase warga nagari yang mengakses kredit dari layanan keuangan informal'],
                    ['8.2.10', 'Tersedianya lembaga layanan keuangan formal di nagari'],
                ]],
                ['8.3', 'Tercapainya tingkat pengangguran terbuka menjadi 3%', null, [
                    ['8.3.1', 'Persentase warga Nagari sedang mencari pekerjaan atau mempersiapkan usaha baru dalam 1 minggu terakhir, berdasarkan jenis kelamin dan kelompok umur'],
                ]],
                ['8.4', 'Menurunnya persentase kelompok usia muda (berusia 15-24 tahun) yang sedang tidak sekolah, tidak bekerja atau tidak mengikuti pelatihan', null, [
                    ['8.4.1', 'Persentase kelompok usia muda (berusia 15-24 tahun) yang dalam 1 minggu terakhir sedang tidak sekolah, tidak bekerja atau tidak mengikuti pelatihan'],
                ]],
                ['8.5', 'Menurunnya persentase pekerja anak, hingga 0%', null, [
                    ['8.5.1', 'Persentase anak usia 5-18 tahun yang bekerja, menurut jenis kelamin dan kelompok umur'],
                ]],
                ['8.6', 'Meningkatnya persentase tenaga kerja Nagari yang mendapatkan pelindungan kesehatan dan keselamatan kerja di tempat kerjanya, hingga 100%', null, [
                    ['8.6.1', 'Persentase warga Nagari yang bekerja menjadi peserta SJSN bidang ketenagakerjaan'],
                ]],
                ['8.7', 'Meningkatnya jumlah wisatawan mancanegara dan/atau wisatawan nusantara yang berkunjung ke Nagari', null, [
                    ['8.7.1', 'Laju pertumbuhan jumlah wisatawan mancanegara dan/atau wisatawan nusantara yang berkunjung ke Nagari'],
                ]],
                ['8.8', 'Meningkatnya jumlah wisatawan mancanegara dan/atau wisatawan nusantara yang berkunjung dan menginap di Nagari', null, [
                    ['8.8.1', 'Laju pertumbuhan per tahun jumlah wisatawan mancanegara yang berkunjung dan menginap di Nagari'],
                    ['8.8.2', 'Laju pertumbuhan per tahun jumlah wisatawan nusantara yang berkunjung dan menginap di Nagari'],
                ]],
                ['8.9', 'Meningkatnya pendapatan asli Nagari bersumber dari sektor pariwisata', null, [
                    ['8.9.1', 'Persentase Pendapatan Asli Nagari yang bersumber dari sektor pariwisata'],
                ]],
                ['8.10', 'Meningkatnya akses permodalan formal di Nagari', null, [
                    ['8.10.1', 'Jumlah layanan keuangan formal yang tersedia di Nagari'],
                    ['8.10.2', 'Persentase layanan keuangan formal di Nagari yang bersumber dari perbankan'],
                    ['8.10.3', 'Jumlah kantor bank, agen bank, dan/atau ATM di wilayah Nagari'],
                    ['8.10.4', 'Tersedianya badan usaha milik Nagari penyedia layanan keuangan formal'],
                ]],
                ['8.11', 'Meningkatnya persentase warga Nagari dan/atau usaha mikro kecil menengah yang mengakses permodalan formal', null, [
                    ['8.11.1', 'Persentase warga Nagari yang mengakses layanan keuangan formal dari badan usaha milik Nagari, koperasi di Nagari, dan/atau layanan keuangan formal lainnya yang ada di Nagari'],
                    ['8.11.2', 'Persentase usaha mikro kecil menengah di Nagari yang mendapatkan kredit dari layanan keuangan formal'],
                    ['8.11.3', 'Persentase keuangan Nagari yang digunakan untuk modal badan usaha milik Nagari'],
                ]],
            ],

            // ── POIN 9 — Infrastruktur dan Inovasi Nagari Sesuai Kebutuhan ──
            9 => [
                ['9.1', 'Tersedianya jalan Nagari dan jalan antarDesa yang dapat dilewati oleh kendaraan roda 4 (empat) minimal truk yang berkapasitas 4 (empat) ton dengan 6 (enam) roda yaitu 2 (dua) roda depan dan 4 (empat) roda belakang', null, [
                    ['9.1.1', 'Proporsi panjang jalan Nagari yang dapat dilewati oleh kendaraan minimal truk, berkapasitas 4 (empat) ton dengan 6 (enam) roda yaitu 2 (dua) roda depan dan 4 (empat) roda belakang, terhadap total panjang jalan Nagari'],
                ]],
                ['9.2', 'Tersedianya dermaga dan/atau tambatan perahu di Nagari', null, [
                    ['9.2.1', 'Tersedianya dermaga dan/atau tambatan perahu yang dikelola oleh Nagari pada pusat permukiman, kampung, atau pulau kecil'],
                ]],
                ['9.3', 'Meningkatnya jumlah pendapatan industri pengolahan di Nagari', null, [
                    ['9.3.1', 'Jumlah industri pengolahan di Nagari'],
                    ['9.3.2', 'Jumlah hasil produksi (output) dan peredaran usaha (omzet) industri pengolahan di Nagari'],
                ]],
                ['9.4', 'Meningkatnya jumlah tenaga kerja yang terserap di sektor industri pengolahan di Nagari', null, [
                    ['9.4.1', 'Proporsi tenaga kerja Nagari yang terserap di sektor industri pengolahan'],
                ]],
                ['9.5', 'Meningkatnya jumlah pendapatan industri mikro, kecil, dan menengah di Nagari', null, [
                    ['9.5.1', 'Jumlah hasil produksi (output) dan peredaran usaha (omzet) industri mikro, kecil, menengah, dan besar di Nagari'],
                ]],
                ['9.6', 'Terdapat akses permodalan formal untuk industri mikro, kecil dan menengah', null, [
                    ['9.6.1', 'Proporsi industri mikro, kecil dan menengah di Nagari yang mendapatkan pinjaman modal atau kredit usaha'],
                ]],
                ['9.7', 'Meningkatnya jumlah inovasi di Nagari', null, [
                    ['9.7.1', 'Jumlah inovasi di Nagari'],
                ]],
                ['9.8', 'Meningkatnya persentase Inovasi di Nagari yang dicatat dan disebarluaskan mencapai 100%', null, [
                    ['9.8.1', 'Persentase Inovasi di Nagari yang dipublikasikan dan/atau dilombakan'],
                ]],
                ['9.9', 'Meningkatnya persentase rumah tangga dengan akses pitalebar seluler (mobile broadband) mencapai 100%', null, [
                    ['9.9.1', 'Persentase rumah tangga nagari dengan akses pitalebar seluler (mobile broadband)'],
                ]],
            ],

            // ── POIN 10 — Nagari Tanpa Kesenjangan ──
            10 => [
                ['10.1', 'Menurunnya ketimpangan pendapatan Rumah Tangga di Nagari', null, [
                    ['10.1.1', 'Tingkat ketimpangan pendapatan keluarga di Nagari'],
                    ['10.1.2', 'Tingkat ketimpangan penguasaan tanah pertanian di Nagari'],
                ]],
                ['10.2', 'Tidak ada warga Nagari yang berstatus miskin', null, [
                    ['10.2.1', 'Persentase warga Nagari yang hidup di bawah garis kemiskinan daerah kabupaten/kota menurut jenis kelamin dan kelompok umur'],
                    ['10.2.2', 'Persentase warga miskin ekstrem dan keluarga dalam kategori miskin ekstrem di Nagari'],
                ]],
                ['10.3', 'Meningkatnya inklusi sosial, ekonomi, politik, dan budaya bagi semua warga Nagari', null, [
                    ['10.3.1', 'Persentase warga Nagari yang hidup di bawah 50% dari median pendapatan menurut jenis kelamin'],
                ]],
                ['10.4', 'Meningkatnya persentase warga Nagari yang berpartisipasi dalam pemilihan kepala Nagari mencapai 100%', null, [
                    ['10.4.1', 'Persentase warga Nagari yang berpartisipasi dalam pemilihan kepala Nagari'],
                ]],
                ['10.5', 'Meningkatnya persentase perwakilan kelompok masyarakat yang berpartisipasi dalam musyawarah Nagari mencapai 100%', null, [
                    ['10.5.1', 'Persentase perwakilan kelompok masyarakat yang hadir dalam musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 tahun terakhir'],
                    ['10.5.2', 'Persentase warga Nagari yang hadir tetapi tidak memiliki hak suara dalam musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 tahun terakhir'],
                ]],
                ['10.6', 'Seluruh produk hukum Nagari tidak diskriminatif', null, [
                    ['10.6.1', 'Jumlah produk hukum Nagari yang tidak diskriminatif'],
                ]],
                ['10.7', 'Meningkatnya warga nagari peserta sistem jaminan sosial nasional bidang ketenagakerjaan mencapai 100%', null, [
                    ['10.7.1', 'Persentase warga Nagari yang bekerja sebagai buruh, karyawan, atau pegawai yang merupakan peserta sistem jaminan sosial nasional bidang ketenagakerjaan'],
                ]],
                ['10.8', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan pekerja migran Indonesia', null, [
                    ['10.8.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan pekerja migran Indonesia'],
                ]],
                ['10.9', 'Adanya pelayanan pelindungan pekerja migran Indonesia oleh Nagari', null, [
                    ['10.9.1', 'Tersedianya pelayanan pelindungan pekerja migran Indonesia oleh Nagari'],
                ]],
            ],

            // ── POIN 11 — Kawasan Pemukiman Nagari Aman dan Nyaman ──
            11 => [
                ['11.1', 'Menjamin akses bagi semua rumah tangga terhadap perumahan yang layak, aman, dan terjangkau', null, [
                    ['11.1.1', 'Persentase rumah tangga yang menempati hunian layak, aman dan terjangkau'],
                ]],
                ['11.2', 'Menyediakan akses terhadap transportasi umum massal yang nyaman didekati dengan jarak akses paling jauh radius 0,5 (nol koma lima) kilometer dari lokasi pemukiman penduduk', null, [
                    ['11.2.1', 'Persentase warga Nagari memiliki akses terhadap transportasi umum massal yang nyaman didekati dengan jarak akses paling jauh radius 0,5 (nol koma lima) kilometer dari lokasi pemukiman penduduk'],
                    ['11.2.2', 'Persentase warga Nagari yang pernah mengakses transportasi umum massal dengan jarak akses paling jauh radius 0,5 (nol koma lima) kilometer dari lokasi pemukiman penduduk atau kurang, dalam 3 bulan terakhir'],
                ]],
                ['11.3', 'Meningkatnya komitmen nagari untuk melindungi, mengembangkan, dan melestarikan kebudayaan Nagari dan warisan alam melalui penganggaran Nagari', null, [
                    ['11.3.1', 'Persentase anggaran pendapatan dan belanja Nagari yang dialokasikan untuk pelindungan, pengembangan, dan pelestarian kebudayaan Nagari dan warisan alam'],
                ]],
                ['11.4', 'Menurunnya persentase warga Nagari yang terdampak bencana, serta mengurangi dampak kerusakan infrastuktur dan gangguan layanan dasar akibat bencana', null, [
                    ['11.4.1', 'Persentase rumah tangga yang mengalami kerugian materiil akibat bencana dalam 6 bulan terakhir'],
                    ['11.4.2', 'Persentase warga Nagari yang mengungsi akibat bencana dalam 6 bulan terakhir'],
                    ['11.4.3', 'Persentase warga Nagari yang meninggal akibat terdampak bencana dalam 6 bulan terakhir'],
                    ['11.4.4', 'Persentase warga Nagari yang hilang akibat terdampak bencana dalam 6 bulan terakhir'],
                    ['11.4.5', 'Persentase warga Nagari yang terluka/sakit akibat terdampak bencana dalam 6 bulan terakhir'],
                    ['11.4.6', 'Persentase kerusakan pada infrastruktur vital dan fasilitas dasar, akibat bencana'],
                ]],
                ['11.5', 'Mengurangi pencemaran lingkungan Nagari yang disebabkan oleh sampah', null, [
                    ['11.5.1', 'Persentase rumah tangga yang terlayani pengelolaan sampah selama 1 bulan terakhir'],
                    ['11.5.2', 'Persentase rumah tangga yang mengolah dan/atau mendaur ulang sampah selama 1 bulan terakhir'],
                    ['11.5.3', 'Persentase rumah tangga yang tidak melakukan pembakaran sampah dalam 1 bulan terakhir'],
                    ['11.5.4', 'Persentase rumah tangga yang tidak menggunakan kayu bakar sebagai bahan sumber energi utama untuk memasak'],
                ]],
                ['11.6', 'Nagari memiliki ruang publik dan ruang terbuka hijau yang aman, inklusif dan mudah dijangkau terutama bagi perempuan dan anak, lanjut usia, dan penyandang disabilitas', null, [
                    ['11.6.1', 'Tersedianya ruang publik dan ruang terbuka hijau di Nagari yang aman, inklusif, dan mudah diakses di nagari'],
                ]],
                ['11.7', 'Mengurangi korban kejahatan kekerasan dan/atau pelecehan seksual yang terjadi di Nagari', null, [
                    ['11.7.1', 'Persentase warga Nagari yang menjadi korban kejahatan kekerasan dalam 1 tahun terakhir'],
                    ['11.7.2', 'Persentase warga Nagari yang menjadi korban kejahatan pelecehan seksual dalam 1 tahun terakhir'],
                ]],
                ['11.8', 'Nagari memiliki dokumen rencana tata ruang wilayah Nagari', null, [
                    ['11.8.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari tentang rencana tata ruang dan wilayah Nagari'],
                ]],
                ['11.9', 'Nagari memiliki dokumen perencanaan mitigasi serta adaptasi terhadap perubahan iklim dan bencana', null, [
                    ['11.9.1', 'Tersedianya peta potensi rawan bencana'],
                    ['11.9.2', 'Tersedianya penilaian ketangguhan Nagari terhadap bencana dan perubahan iklim'],
                    ['11.9.3', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari mengenai mitigasi serta adaptasi perubahan iklim dan bencana'],
                    ['11.9.4', 'Tersedianya rencana aksi Nagari mengenai mitigasi serta adaptasi perubahan iklim dan bencana'],
                ]],
            ],

            // ── POIN 12 — Konsumsi dan Produksi Nagari Sadar Lingkungan ──
            12 => [
                ['12.1', 'Mengurangi limbah makanan dan/atau limbah pasca panen', null, [
                    ['12.1.1', 'Persentase warga Nagari pelaku usaha pertanian di Nagari yang melaksanakan upaya pengurangan limbah pasca panen yang terlibat sektor pertanian'],
                    ['12.1.2', 'Persentase rumah tangga yang melakukan pengelolaan sampah limbah sisa makanan dalam 3 (tiga) bulan terakhir'],
                ]],
                ['12.2', 'Meningkatkan pengelolaan bahan kimia dan semua jenis limbah B3 yang dibuang sesuai ketentuan yang berdampak pada pelestarian lingkungan', null, [
                    ['12.2.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pengelolaan bahan kimia dan semua jenis limbah B3 yang berdampak pada pelestarian lingkungan'],
                    ['12.2.2', 'Persentase rumah tangga yang menggunakan bahan kimia beracun dan/atau berbahaya untuk kegiatan sehari-hari'],
                    ['12.2.3', 'Persentase rumah tangga yang melakukan pengelolaan limbah B3'],
                ]],
                ['12.3', 'Sampah di wilayah Nagari yang dikelola sesuai ketentuan', null, [
                    ['12.3.1', 'Persentase rumah tangga yang mengolah dan/atau mendaur ulang sampah selama 3 bulan terakhir'],
                    ['12.3.2', 'Jumlah unit daur ulang sampah di Nagari'],
                ]],
                ['12.4', 'Meningkatnya jumlah usaha yang ada di nagari untuk mengelola limbah usaha dan tidak menimbulkan pencemaran lingkungan', null, [
                    ['12.4.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai kegiatan usaha yang tidak menimbulkan pencemaran lingkungan'],
                    ['12.4.2', 'Persentase Jumlah UMKM, Koperasi dan/atau BUMDesa yang melakukan pengelolaan limbah usaha'],
                ]],
                ['12.5', 'Meningkatnya produk UMKM, Koperasi dan/atau BUMDesa yang memiliki produk ramah lingkungan', null, [
                    ['12.5.1', 'Jumlah UMKM, Koperasi dan/atau BUMDesa yang menghasilkan produk ramah lingkungan'],
                ]],
                ['12.6', 'Mendorong praktik pengadaan barang dan/atau jasa dalam Pembangunan Nagari yang ramah lingkungan', null, [
                    ['12.6.1', 'Tersedianya kegiatan pengadaan barang dan/atau jasa dalam Pembangunan Nagari yang ramah lingkungan'],
                ]],
                ['12.7', 'Terdapat lembaga kemasyarakatan dan/atau masyarakat Nagari/komunitas masyarakat yang peduli dan berbudaya lingkungan hidup di Nagari', null, [
                    ['12.7.1', 'Jumlah lembaga kemasyarakatan dan/atau masyarakat Nagari/komunitas masyarakat yang peduli dan berbudaya lingkungan hidup'],
                ]],
                ['12.8', 'Meningkatnya akses rumah tangga terhadap energi terbarukan di Nagari', null, [
                    ['12.8.1', 'Jumlah unit pembangkit listrik dan/atau sumber energi terbarukan yang terpasang dan berfungsi di Nagari'],
                    ['12.8.2', 'Persentase Rumah Tangga yang memiliki akses terhadap energi terbarukan'],
                ]],
                ['12.9', 'Mengembangkan wisata Nagari berkelanjutan melalui pemberdayaan lembaga ekonomi dan masyarakat komunitas yang ada di Nagari', null, [
                    ['12.9.1', 'Jumlah lokasi wisata Nagari berkelanjutan yang dikelola oleh BUMDesa'],
                    ['12.9.2', 'Jumlah lokasi wisata Nagari berkelanjutan yang dikelola oleh Kelompok masyarakat'],
                ]],
            ],

            // ── POIN 13 — Nagari Tanggap Perubahan Iklim ──
            13 => [
                ['13.1', 'Menurunnya persentase warga Nagari yang terdampak bencana hidrometeorologi (korban dan kerugian material)', null, [
                    ['13.1.1', 'Persentase warga Nagari yang mengalami kerugian materiil akibat bencana hidrometeorologi'],
                    ['13.1.2', 'Persentase warga Nagari yang mengungsi akibat bencana hidrometeorologi'],
                    ['13.1.3', 'Persentase warga Nagari yang meninggal akibat terdampak bencana hidrometeorologi'],
                    ['13.1.4', 'Persentase warga Nagari yang hilang akibat terdampak bencana hidrometeorologi'],
                    ['13.1.5', 'Persentase warga Nagari yang terluka/sakit akibat terdampak bencana hidrometeorologi'],
                ]],
                ['13.2', 'Nagari memiliki dokumen perencanaan mitigasi dan adaptasi terhadap bencana', null, [
                    ['13.2.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai adaptasi dan mitigasi bencana'],
                    ['13.2.2', 'Tersedianya peta potensi rawan bencana'],
                    ['13.2.3', 'Tersedianya sistem peringatan dini bencana'],
                    ['13.2.4', 'Tersedianya rencana aksi Nagari mengenai mitigasi dan adaptasi bencana'],
                ]],
                ['13.3', 'Nagari mampu mengintegrasikan tindakan antisipasi perubahan iklim ke dalam kebijakan serta perencanaan pembangunan Nagari serta Pemberdayaan Masyarakat Nagari', null, [
                    ['13.3.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari mengenai mitigasi dan adaptasi perubahan iklim'],
                    ['13.3.2', 'Jumlah kegiatan dalam rangka penurunan emisi gas rumah kaca'],
                ]],
                ['13.4', 'Nagari melakukan penilaian ketangguhan bencana hidrometeorologi dan perubahan iklim', null, [
                    ['13.4.1', 'Tersedianya hasil penilaian ketangguhan Nagari terhadap bencana hidrometeorologi dan perubahan iklim'],
                ]],
                // Sumber PDF mengulang nomor 13.4 (isi berbeda) → disambiguasi '13.4b'.
                ['13.4b', 'Meningkatnya Kesiapsiagaan Nagari terhadap bencana dan perubahan iklim', null, [
                    ['13.4b.1', 'Tersedianya sistem peringatan dini bencana'],
                ]],
                ['13.5', 'Adanya kelompok masyarakat dan/atau relawan yang mampu melaksanakan kegiatan komunikasi, informasi, dan edukasi mengenai pengurangan risiko bencana hidrometeorologi serta mitigasi dan adaptasi perubahan iklim', null, [
                    ['13.5.1', 'Jumlah kelompok masyarakat dan/atau relawan yang mampu melaksanakan kegiatan komunikasi, informasi, dan edukasi, mengenai pengurangan risiko bencana hidrometeorologi serta mitigasi dan adaptasi perubahan iklim'],
                ]],
                ['13.6', 'Adanya lembaga kemasyarakatan dan/atau masyarakat Nagari/komunitas/lembaga masyarakat yang peduli dan berbudaya lingkungan hidup', null, [
                    ['13.6.1', 'Jumlah lembaga kemasyarakatan dan/atau masyarakat Nagari yang peduli dan berbudaya lingkungan hidup'],
                ]],
                ['13.7', 'Adanya alokasi anggaran pendapatan dan belanja Nagari untuk pendanaan mitigasi dan adaptasi perubahan iklim yang dilakukan nagari', null, [
                    ['13.7.1', 'Persentase anggaran pendapatan dan belanja Nagari untuk pendanaan mitigasi dan adaptasi perubahan iklim'],
                ]],
            ],

            // ── POIN 14 — Nagari Peduli Lingkungan Laut ──
            14 => [
                ['14.1', 'Mengurangi pencemaran laut melalui kegiatan pencegahan, pengendalian, dan penanganan pencemaran yang dilakukan oleh pemerintah Nagari, masyarakat Nagari, dan/atau pemangku kepentingan lainnya yang terkait', null, [
                    ['14.1.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari mengenai larangan pembuangan sampah dan/atau limbah ke sungai dan/atau laut'],
                    ['14.1.2', 'Persentase rumah tangga yang membuang sampah dan/atau limbah ke sungai dan/atau laut'],
                ]],
                ['14.2', 'Menjaga persediaan sumber daya ikan secara berkelanjutan', null, [
                    ['14.2.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pengelolaan sumber daya ikan secara berkelanjutan'],
                    ['14.2.2', 'Persentase pelaku penangkapan ikan yang menggunakan alat tangkap ramah lingkungan sesuai dengan ketentuan'],
                ]],
                ['14.3', 'Terwujudnya kawasan konservasi atau zona larang tangkap perairan laut untuk menjaga keberlanjutan sumber daya ikan dan ekosistem pesisir yang ditetapkan secara partisipatif (minimal setara 33% dari luas wilayah Nagari)', null, [
                    ['14.3.1', 'Persentase wilayah perairan laut yang ditetapkan secara partisipatif sebagai kawasan konservasi atau zona larang tangkap untuk menjaga keberlanjutan sumber daya ikan dan ekosistem pesisir'],
                    ['14.3.2', 'Jumlah luas kawasan lindung perairan laut di Nagari'],
                ]],
                ['14.4', 'Menurunnya praktik penangkapan ikan berlebih dan ilegal fishing', null, [
                    ['14.4.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai larangan praktik melakukan penangkapan ikan berlebih dan ilegal fishing'],
                    ['14.4.2', 'Persentase praktik penangkapan ikan yang menggunakan alat tangkap yang merusak lingkungan, misalnya: bahan peledak, racun, setrum, dan pukat tarik (trawl)'],
                ]],
                ['14.5', 'Tersedianya kebijakan terkait akses untuk nelayan kecil dan/atau pembudidaya ikan berskala kecil terhadap sumber daya laut dan pasar', null, [
                    ['14.5.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai akses nelayan kecil dan/atau pembudidaya ikan berskala kecil/tradisional untuk mengakses sumber daya laut dan pasar'],
                    ['14.5.2', 'Persentase anggaran pendapatan dan belanja Nagari yang dialokasikan untuk membiayai bantuan ekonomi produktif bagi nelayan kecil dan/atau pembudidaya ikan berskala kecil'],
                    ['14.5.3', 'Persentase nelayan kecil dan/atau pembudidaya ikan berskala kecil yang mendapat bantuan pendidikan, kesehatan, dan/atau pelindungan sosial'],
                ]],
            ],

            // ── POIN 15 — Nagari Peduli Lingkungan Darat ──
            15 => [
                ['15.1', 'Menjamin pemanfaatan, restorasi, dan pelestarian hutan Nagari secara berkelanjutan', null, [
                    ['15.1.1', 'Persentase luas hutan di wilayah Nagari'],
                    ['15.1.2', 'Persentase luas hutan di wilayah Nagari yang milik Nagari'],
                    ['15.1.3', 'Persentase luas hutan di wilayah Nagari yang bukan milik Nagari'],
                    ['15.1.4', 'Luas hutan yang dikelola Nagari melalui mekanisme perhutanan sosial'],
                    // Sumber PDF mengulang nomor 15.1.4 (isi berbeda) → disambiguasi '15.1.4b'.
                    ['15.1.4b', 'Jumlah kegiatan pelestarian hutan milik Nagari dan/atau hutan yang dikelola Nagari melalui mekanisme perhutanan sosial'],
                    ['15.1.5', 'Persentase wilayah Nagari yang masuk dalam kawasan konservasi'],
                    ['15.1.6', 'Persentase warga Nagari yang memanfaatkan sumber daya alam tanpa mengancam keanekaragaman hayati'],
                ]],
                ['15.2', 'Terlaksananya pengelolaan hutan secara lestari oleh Nagari', null, [
                    ['15.2.1', 'Luas kawasan hutan yang dikelola secara lestari oleh Nagari dengan status milik Nagari dan/atau melalui skema perhutanan sosial'],
                ]],
                ['15.3', 'Terlaksananya pemulihan hutan rusak dan rehabilitasi lahan kritis di wilayah Nagari', null, [
                    ['15.3.1', 'Luas hutan rusak di wilayah Nagari'],
                    ['15.3.2', 'Luas lahan kritis di wilayah Nagari'],
                ]],
                ['15.4', 'Meningkatnya kegiatan budidaya spesies tanaman dan satwa liar yang terancam punah', null, [
                    ['15.4.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan terhadap tanaman dan satwa liar'],
                    ['15.4.2', 'Jumlah kegiatan budidaya spesies tanaman dan satwa liar yang terancam punah'],
                ]],
                ['15.5', 'Tersedianya kegiatan penangkaran tanaman dan satwa liar yang terancam punah', null, [
                    ['15.5.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pemanfaatan sumber daya alam hayati'],
                ]],
                ['15.6', 'Meningkatnya jumlah kasus perusakan lingkungan yang ditindak secara hukum', null, [
                    ['15.6.1', 'Jumlah laporan aktivitas perburuan dan/atau perdagangan ilegal yang menjualbelikan tanaman dan satwa liar yang terancam punah yang diproses secara hukum melalui jalur pengadilan (litigasi)'],
                ]],
                // Sumber PDF mengulang nomor 15.6 (isi berbeda) → disambiguasi '15.6b'.
                ['15.6b', 'Adanya kebijakan di Nagari mengenai pencegahan dan pengendalian jenis asing invasif', null, [
                    ['15.6b.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pencegahan atau pengendalian jenis asing invasif'],
                ]],
                ['15.7', 'Mengurangi aktivitas perburuan dan/atau perdagangan ilegal tanaman dan satwa liar yang terancam punah', null, [
                    ['15.7.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai larangan pemburuan dan perdagangan ilegal tanaman dan satwa liar yang terancam punah'],
                    ['15.7.2', 'Jumlah kasus perburuan dan/atau perdagangan ilegal tanaman dan satwa liar yang terancam punah di wilayah Nagari'],
                    ['15.7.3', 'Jumlah laporan mengenai aktivitas perburuan dan/atau perdagangan ilegal tanaman dan satwa liar yang terancam punah di wilayah Nagari melalui jalur pengadilan'],
                ]],
            ],

            // ── POIN 16 — Nagari Damai Berkeadilan ──
            16 => [
                ['16.1', 'Menurunnya jumlah kasus pembunuhan di Nagari, hingga 0', null, [
                    ['16.1.1', 'Jumlah kasus kejahatan pembunuhan di Nagari pada satu tahun terakhir'],
                ]],
                ['16.2', 'Menurunnya jumlah konflik sosial di Nagari, hingga 0', null, [
                    ['16.2.1', 'Jumlah konflik sosial di Nagari pada satu tahun terakhir'],
                ]],
                ['16.3', 'Menurunnya jumlah kasus kekerasan terhadap perempuan dan/atau laki-laki di Nagari, hingga 0', null, [
                    ['16.3.1', 'Jumlah kasus kekerasan terhadap warga di Nagari dalam satu tahun terakhir'],
                    ['16.3.2', 'Jumlah korban kekerasan terhadap warga di Nagari menurut jenis kelamin dalam satu tahun terakhir'],
                ]],
                ['16.4', 'Menurunnya jumlah kasus perkelahian di Nagari, hingga 0', null, [
                    ['16.4.1', 'Jumlah kasus perkelahian di Nagari dalam satu tahun terakhir'],
                ]],
                ['16.5', 'Menurunnya jumlah kasus kriminalitas di Nagari, hingga 0', null, [
                    ['16.5.1', 'Jumlah kasus kriminalitas di Nagari dalam satu tahun terakhir'],
                ]],
                ['16.6', 'Meningkatnya keamanan lingkungan permukiman Nagari', null, [
                    ['16.6.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai sistem keamanan lingkungan'],
                    ['16.6.2', 'Proporsi pos ronda di Nagari terhadap jumlah dusun dan/atau rukun warga'],
                    ['16.6.3', 'Rata-rata jumlah kegiatan ronda atau keamanan lingkungan di dusun dan/atau rukun warga dalam 1 (satu) minggu'],
                ]],
                ['16.7', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai perlindungan hak anak', null, [
                    ['16.7.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai perlindungan anak'],
                ]],
                ['16.8', 'Menurunnya jumlah kasus kekerasan terhadap anak di Nagari, hingga 0', null, [
                    ['16.8.1', 'Jumlah kasus kekerasan terhadap anak di Nagari dalam satu tahun terakhir'],
                    ['16.8.2', 'Jumlah korban kekerasan terhadap anak di Nagari berdasarkan jenis kelamin dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.9', 'Menurunnya persentase warga Nagari yang menjadi korban perdagangan orang di Nagari, hingga 0%', null, [
                    ['16.9.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pencegahan perdagangan orang'],
                    ['16.9.2', 'Jumlah warga Nagari yang menjadi korban perdagangan orang di Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.10', 'Tersedia layanan bantuan hukum oleh Nagari', null, [
                    ['16.10.1', 'Tersedianya pos bantuan hukum di Nagari'],
                    ['16.10.2', 'Jumlah warga Nagari yang menjadi paralegal Nagari'],
                    ['16.10.3', 'Jumlah kegiatan konsultasi hukum di pos bantuan hukum Nagari dalam 1 (satu) tahun terakhir'],
                    ['16.10.4', 'Jumlah kegiatan konsultasi hukum yang diajukan oleh warga Nagari kepada pos bantuan hukum Nagari dalam 1 (satu) tahun terakhir'],
                    ['16.10.5', 'Jumlah kegiatan penyelesaian masalah di pos bantuan hukum Nagari melalui tata cara non-litigasi, dalam 1 (satu) tahun terakhir'],
                    ['16.10.6', 'Jumlah kegiatan penyuluhan hukum di Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.11', 'Meningkatnya persentase warga miskin yang menerima bantuan hukum, hingga 100%', null, [
                    ['16.11.1', 'Persentase warga miskin yang menerima bantuan hukum Nagari melalui tata cara litigasi dalam 1 (satu) tahun terakhir'],
                    ['16.11.2', 'Persentase warga miskin yang menerima bantuan hukum Nagari melalui tata cara non-litigasi dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.12', 'Menurunnya jumlah kasus tindak pidana korupsi, hingga 0', null, [
                    ['16.12.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai akuntabilitas sosial'],
                    ['16.12.2', 'Jumlah kasus tindak pidana korupsi dalam kegiatan penyelenggaraan Nagari dalam 1 (satu) tahun terakhir'],
                    ['16.12.3', 'Jumlah musyawarah Nagari untuk penyampaian laporan pertanggungjawaban pelaksanaan pembangunan Nagari dari kepala Nagari kepada BPD dan masyarakat Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.13', 'Tersedia kegiatan pencegahan tindak pidana korupsi', null, [
                    ['16.13.1', 'Jumlah kegiatan pencegahan tindak pidana korupsi di Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.14', 'Tersedia kegiatan penanganan tindak pidana korupsi', null, [
                    ['16.14.1', 'Jumlah kasus tindak pidana korupsi dalam penyelenggaraan Nagari yang dilaporkan kepada penegak hukum'],
                    ['16.14.2', 'Jumlah kasus tindak pidana korupsi dalam penyelenggaraan Nagari yang dilakukan penindakan oleh penegak hukum'],
                ]],
                ['16.15', 'Meningkatnya presentase serapan anggaran pendapatan dan belanja Nagari', null, [
                    ['16.15.1', 'Persentase serapan anggaran pendapatan dan belanja Nagari dalam 1 (satu) tahun anggaran terakhir'],
                ]],
                ['16.16', 'Tersedia penilaian kepuasan masyarakat terhadap layanan publik di Nagari', null, [
                    ['16.16.1', 'Tingkat kepuasan warga Nagari terhadap layanan publik dalam 1 (satu) tahun terakhir yang diukur menggunakan kartu penilaian warga (community scorecard)'],
                    ['16.16.2', 'Jumlah pengaduan warga Nagari kepada pemerintah Nagari dan/atau BPD mengenai penyelenggaraan Nagari, dalam 1 (satu) tahun terakhir'],
                    ['16.16.3', 'Persentase pengaduan warga Nagari mengenai penyelenggaraan Nagari yang ditindaklanjuti oleh pemerintah Nagari dan/atau BPD, dalam 1 (satu) tahun terakhir'],
                    ['16.16.4', 'Persentase warga Nagari yang memanfaatkan kartu penilaian warga (community scorecard)'],
                    ['16.16.5', 'Persentase warga Nagari yang menyampaikan pengaduan kepada pemerintah Nagari dan/atau BPD mengenai penyelenggaraan Nagari, dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.17', 'Persentase perempuan menjadi anggota BPD dan/atau perangkat Nagari, minimal 30% dari kuota', null, [
                    ['16.17.1', 'Proporsi keterwakilan perempuan sebagai anggota BPD terhadap jumlah anggota BPD, dalam 1 (satu) tahun terakhir'],
                    ['16.17.2', 'Proporsi perempuan sebagai perangkat Nagari terhadap jumlah perangkat Nagari, dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.18', 'Meningkatnya persentase perempuan yang berpartisipasi dalam musyawarah Nagari minimal 30%', null, [
                    ['16.18.1', 'Persentase perempuan yang hadir dalam musyawarah Nagari untuk penyusunan RKP Nagari, dalam 1 tahun terakhir'],
                    ['16.18.2', 'Persentase perempuan yang hadir tetapi tidak memiliki hak suara dalam musyawarah Nagari untuk penyusunan RKP Nagari, dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.19', 'Meningkatnya persentase warga Nagari yang memiliki akte kelahiran hingga 100%', null, [
                    ['16.19.1', 'Persentase warga Nagari yang memiliki akta kelahiran dalam 6 (enam) bulan terakhir'],
                ]],
                ['16.20', 'Meningkatnya persentase nilai capaian pelindungan hak asasi manusia bagi warga Nagari yang marginal dan rentan, hingga 100%', null, [
                    ['16.20.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pelindungan hak asasi manusia bagi warga Nagari yang marginal dan rentan'],
                    ['16.20.2', 'Persentase warga Nagari yang masuk dalam kelompok marginal dan rentan yang memperoleh bantuan langsung tunai, dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.21', 'Tersedia informasi Nagari', null, [
                    ['16.21.1', 'Tersedianya informasi tentang penyelenggaraan Nagari'],
                ]],
                ['16.22', 'Tersedia akses masyarakat terhadap informasi Nagari', null, [
                    ['16.22.1', 'Jumlah warga Nagari yang mengakses SID, dalam 1 (satu) tahun terakhir'],
                ]],
                ['16.23', 'Tersedianya layanan pengaduan masyarakat melalui pos bantuan hukum Nagari terkait dugaan pelanggaran hak asasi manusia', null, [
                    ['16.23.1', 'Jumlah pengaduan masyarakat melalui pos bantuan hukum Nagari terkait dugaan pelanggaran hak asasi manusia'],
                    ['16.23.2', 'Persentase pengaduan masyarakat terkait dugaan pelanggaran hak asasi manusia yang diterima dan ditindaklanjuti oleh pos bantuan hukum Nagari'],
                ]],
            ],

            // ── POIN 17 — Kemitraan untuk Pembangunan Nagari ──
            17 => [
                ['17.1', 'Meningkatnya kontribusi pendapatan asli Nagari terhadap anggaran pendapatan belanja Nagari', null, [
                    ['17.1.1', 'Laju pertumbuhan pendapatan asli Nagari dari tahun ke tahun'],
                    ['17.1.2', 'Proporsi pendapatan asli Nagari terhadap total pendapatan Nagari'],
                ]],
                ['17.2', 'Meningkatnya kontribusi dana swadaya masyarakat Nagari terhadap anggaran pendapatan belanja Nagari', null, [
                    ['17.2.1', 'Persentase dana swadaya masyarakat Nagari terhadap total pendapatan Nagari'],
                    ['17.2.2', 'Persentase pendapatan asli Nagari terhadap total pendapatan Nagari'],
                    ['17.2.3', 'Persentase Dana Nagari terhadap total pendapatan Nagari'],
                    ['17.2.4', 'Persentase Alokasi Dana Nagari (ADD) terhadap total pendapatan Nagari'],
                    ['17.2.5', 'Persentase bagi hasil pajak daerah dan retribusi daerah kabupaten/kota terhadap total pendapatan Nagari'],
                ]],
                ['17.3', 'Meningkatnya kontribusi pembiayaan pembangunan Nagari dari anggaran pendapatan belanja negara', null, [
                    ['17.3.1', 'Persentase anggaran pendapatan dan belanja negara yang dialokasikan untuk program pembangunan Nagari, yang tercatat sebagai pendapatan Nagari, dan dilaksanakan oleh pemerintah Nagari, terhadap total pendapatan Nagari'],
                ]],
                ['17.4', 'Meningkatnya kontribusi bantuan keuangan dari anggaran pendapatan dan belanja daerah', null, [
                    ['17.4.1', 'Persentase bantuan keuangan untuk Nagari dari anggaran pendapatan dan belanja daerah provinsi terhadap total pendapatan Nagari'],
                    ['17.4.2', 'Persentase bantuan keuangan untuk Nagari dari anggaran pendapatan dan belanja daerah kabupaten/kota terhadap total pendapatan Nagari'],
                    ['17.4.3', 'Persentase anggaran pendapatan dan belanja daerah yang dialokasikan untuk program pembangunan Nagari, yang tidak tercatat sebagai pendapatan Nagari, dan tidak dilaksanakan oleh pemerintah Nagari, terhadap total pendapatan Nagari'],
                ]],
                ['17.5', 'Meningkatnya kontribusi pendapatan Nagari lainnya yang sah', null, [
                    ['17.5.1', 'Persentase pendapatan Nagari lainnya yang sah terhadap total pendapatan Nagari'],
                ]],
                ['17.6', 'Meningkatnya kontribusi bantuan hibah dan sumbangan yang tidak mengikat dari pihak ketiga', null, [
                    ['17.6.1', 'Persentase bantuan hibah dan sumbangan yang tidak mengikat dari pihak ketiga terhadap total pendapatan Nagari'],
                ]],
                ['17.7', 'Meningkatnya persentase rumah tangga yang memiliki akses terhadap jaringan internet tetap (fixed internet)', null, [
                    ['17.7.1', 'Persentase rumah tangga yang memiliki akses terhadap jaringan internet tetap (fixed internet)'],
                ]],
                ['17.8', 'Meningkatnya persentase warga Nagari yang memiliki akses terhadap jaringan internet', null, [
                    ['17.8.1', 'Persentase warga Nagari yang memiliki akses terhadap internet'],
                ]],
                ['17.9', 'Meningkatnya nilai komoditas dan/atau produk Nagari yang diekspor ke luar negeri', null, [
                    ['17.9.1', 'Laju pertumbuhan komoditas dan/atau produk Nagari yang diekspor ke luar negeri'],
                ]],
                ['17.10', 'Tersedia Informasi mengenai Data dan Informasi Nagari yang terbuka dan mudah diakses masyarakat Nagari', null, [
                    ['17.10.1', 'Tersedianya Informasi mengenai Data dan Informasi Nagari yang terbuka dan mudah diakses masyarakat Nagari'],
                ]],
                ['17.11', 'Meningkatnya kerja sama Nagari', null, [
                    ['17.11.1', 'Jumlah kerja sama antarDesa dalam satu tahun terakhir'],
                    ['17.11.2', 'Jumlah kerja sama Nagari dengan pihak ketiga dalam satu tahun terakhir'],
                ]],
                // Sumber PDF mengulang nomor 17.10 (indikator 17.12.1) → dinormalkan ke '17.12'.
                ['17.12', 'Meningkatnya permodalan badan usaha milik Nagari dan/atau badan usaha milik Nagari Bersama yang bersumber dari keuangan Nagari', null, [
                    ['17.12.1', 'Laju peningkatan modal badan usaha milik Nagari dan/atau badan usaha milik Nagari Bersama yang bersumber dari anggaran pendapatan dan belanja Nagari'],
                ]],
                ['17.13', 'Tersedia Data dan Informasi Nagari yang terbuka dan mudah diakses masyarakat Nagari', null, [
                    ['17.13.1', 'Tersedianya Data dan Informasi Nagari yang dimutakhirkan secara berkala setiap 6 (enam) bulan'],
                ]],
                ['17.14', 'Meningkatnya pengelolaan Data dan Informasi Nagari', null, [
                    ['17.14.1', 'Persentase anggaran pendapatan dan belanja Nagari yang dialokasikan untuk kegiatan penyusunan serta pemutakhiran Data dan Informasi Nagari'],
                ]],
                ['17.15', 'Tersedia mitra statistik di Nagari', null, [
                    ['17.15.1', 'Tersedianya mitra statistik di Nagari'],
                ]],
                ['17.16', 'Tersedia layanan pencatatan administrasi kependudukan', null, [
                    ['17.16.1', 'Tersedianya data kelahiran yang dimutakhirkan secara berkala setiap 6 (enam) bulan'],
                    ['17.16.2', 'Tersedianya data kematian yang dimutakhirkan secara berkala setiap 6 (enam) bulan'],
                    ['17.16.3', 'Tersedianya data perpindahan penduduk yang dimutakhirkan secara berkala setiap 6 (enam) bulan'],
                ]],
            ],

            // ── POIN 18 — Kelembagaan Nagari Dinamis dan Budaya Nagari Adaptif (Lampiran II) ──
            // sub_tema: 'kelembagaan' (kode 1.x) & 'budaya' (kode 2.x), kode mengikuti Lampiran II.
            18 => [
                ['1.1', 'Tersedia rancangan RKP Nagari yang disusun dengan mendayagunakan SID', 'kelembagaan', [
                    ['1.1.1', 'Tersedianya rancangan RKP Nagari yang disusun dengan mendayagunakan SID, dalam 1 (satu) tahun terakhir'],
                    ['1.1.2', 'Persentase kelompok masyarakat yang mendiskusikan rancangan RKP Nagari dengan mendayagunakan SID dalam 1 (satu) tahun terakhir'],
                ]],
                ['1.2', 'Meningkatnya persentase kelompok masyarakat yang mendiskusikan rancangan RKP Nagari', 'kelembagaan', [
                    ['1.2.1', 'Persentase kelompok masyarakat yang menerima informasi tentang rancangan RKP Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.2.2', 'Persentase kelompok masyarakat yang mendiskusikan rancangan RKP Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['1.3', 'Meningkatnya persentase kelompok masyarakat yang menghadiri musyawarah Nagari dan memiliki hak suara dalam musyawarah Nagari', 'kelembagaan', [
                    ['1.3.1', 'Persentase kelompok masyarakat yang mengirim wakilnya mengikuti musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['1.4', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai Nagari inklusi', 'kelembagaan', [
                    ['1.4.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai Nagari inklusi'],
                ]],
                ['1.5', 'Meningkatnya persentase warga Nagari marginal dan rentan yang menghadiri musyawarah Nagari dan memiliki hak suara dalam musyawarah Nagari', 'kelembagaan', [
                    ['1.5.1', 'Persentase perempuan yang mengikuti musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.5.2', 'Persentase anak yang mengikuti musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.5.3', 'Persentase penyandang disabilitas yang mengikuti musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.5.4', 'Persentase warga lanjut usia yang mengikuti musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.5.5', 'Persentase warga miskin yang mengikuti musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.5.6', 'Persentase anggota masyarakat adat dan/atau kelompok minoritas yang mengikuti musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['1.6', 'Meningkatnya usulan warga Nagari dan/atau kelompok masyarakat Nagari tentang program dan/atau kegiatan Pembangunan Nagari yang dibahas dan diputuskan dalam musyawarah Nagari', 'kelembagaan', [
                    ['1.6.1', 'Persentase usulan warga Nagari dan/atau kelompok masyarakat Nagari tentang program dan/atau kegiatan Pembangunan Nagari yang dibahas dan diputuskan dalam musyawarah Nagari untuk penyusunan RKP Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['1.7', 'Tersedia peraturan Nagari yang mengatur mengenai kewenangan berdasarkan hak asal-usul dan kewenangan lokal berskala Nagari', 'kelembagaan', [
                    ['1.7.1', 'Tersedianya peraturan Nagari yang mengatur mengenai kewenangan berdasarkan hak asal-usul dan kewenangan lokal berskala Nagari'],
                ]],
                ['1.8', 'Meningkatnya persentase program dan/atau kegiatan Pembangunan Nagari berdasarkan kewenangan Nagari', 'kelembagaan', [
                    ['1.8.1', 'Jumlah program dan/atau kegiatan Pembangunan Nagari dalam RKP Nagari yang sesuai dengan daftar kewenangan berdasarkan hak asal-usul dan kewenangan lokal berskala Nagari'],
                    ['1.8.2', 'Jumlah program dan/atau kegiatan Pembangunan Nagari dalam anggaran pendapatan dan belanja Nagari yang sesuai dengan daftar kewenangan berdasarkan hak asal-usul dan kewenangan lokal berskala Nagari'],
                ]],
                ['1.9', 'Meningkatnya persentase program dan/atau kegiatan Pembangunan Nagari yang diputuskan dalam musyawarah Nagari', 'kelembagaan', [
                    ['1.9.1', 'Jumlah program dan/atau kegiatan Pembangunan Nagari dalam rancangan peraturan Nagari tentang RKP Nagari yang diputuskan melalui musyawarah Nagari'],
                    ['1.9.2', 'Jumlah program dan/atau kegiatan Pembangunan Nagari dalam rancangan peraturan Nagari tentang anggaran pendapatan dan belanja Nagari yang diputuskan melalui musyawarah Nagari'],
                    ['1.9.3', 'Jumlah program dan/atau kegiatan Pembangunan Nagari dalam rancangan peraturan Nagari tentang anggaran pendapatan dan belanja Nagari yang disetujui oleh bupati/walikota'],
                ]],
                ['1.10', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai akuntabilitas sosial', 'kelembagaan', [
                    ['1.10.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai akuntabilitas sosial'],
                ]],
                ['1.11', 'Meningkatnya persentase pelaksanaan program dan/atau kegiatan Pembangunan Nagari yang dipantau oleh masyarakat Nagari', 'kelembagaan', [
                    ['1.11.1', 'Tersedianya informasi Nagari mengenai Pembangunan Nagari yang dapat diakses secara terbuka dan lansung oleh warga Nagari'],
                    ['1.11.2', 'Tersedianya informasi mengenai dokumen perencanaan dan anggaran pembangunan Nagari, yaitu: RPJM Nagari, RKP Nagari dan anggaran pendapatan dan belanja Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.11.3', 'Tersedianya informasi mengenai laporan pelaksanaan program dan/atau kegiatan Pembangunan Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.11.4', 'Tersedianya jurnalisme warga melalui media cetak dan/atau media online'],
                    ['1.11.5', 'Persentase warga Nagari yang terlibat aktif dalam jurnalisme warga dalam 1 (satu) tahun terakhir'],
                    ['1.11.6', 'Persentase warga Nagari yang membaca dan/atau mendengar pemberitaan tentang Pembangunan Nagari dalam 3 (tiga) bulan terakhir'],
                    ['1.11.7', 'Persentase pelaksanaan program dan/atau kegiatan Pembangunan Nagari yang dipantau oleh masyarakat Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['1.12', 'Tersedia sarana dan prasarana bagi warga Nagari untuk menyampaikan keluhan dan/atau mengadukan atas pelaksanaan program dan/atau kegiatan Pembangunan Nagari', 'kelembagaan', [
                    ['1.12.1', 'Tersedianya rumah aspirasi warga atau posko pengaduan warga'],
                    ['1.12.2', 'Tersedianya kotak pengaduan warga'],
                    ['1.12.3', 'Tersedianya kartu layanan penilaian warga (community scorecard) dalam SID'],
                ]],
                ['1.13', 'Meningkatnya persentase warga Nagari yang menyampaikan penilaian terhadap kinerja Pembangunan Nagari', 'kelembagaan', [
                    ['1.13.1', 'Persentase warga Nagari menyampaikan penilaian kinerja Pembangunan Nagari melalui kartu layanan penilaian warga (community scorecard) dalam SID pada 1 (satu) tahun terakhir'],
                ]],
                ['1.14', 'Meningkatnya persentase penyelesaian masalah pelaksanaan program dan/atau kegiatan Pembangunan Nagari', 'kelembagaan', [
                    ['1.14.1', 'Persentase masalah pelaksanaan program dan/atau kegiatan Pembangunan Nagari yang berhasil diselesaikan dalam 1 (satu) tahun terakhir'],
                ]],
                ['1.15', 'Meningkatnya persentase pengembangan kapasitas masyarakat Nagari mengenai anggaran Nagari', 'kelembagaan', [
                    ['1.15.1', 'Jumlah pelatihan masyarakat Nagari mengenai anggaran Nagari dalam 1 (satu) tahun terakhir'],
                    ['1.15.2', 'Persentase warga Nagari yang mendapatkan pelatihan mengenai anggaran Nagari dalam 1 (satu) tahun terakhir'],
                ]],
                ['2.1', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pemajuan kebudayaan Nagari', 'budaya', [
                    ['2.1.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai pemajuan kebudayaan Nagari'],
                ]],
                ['2.2', 'Tersedia peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai rencana induk pemajuan kebudayaan Nagari', 'budaya', [
                    ['2.2.1', 'Tersedianya peraturan Nagari dan/atau peraturan kepala Nagari yang mengatur mengenai rencana induk pemajuan kebudayaan Nagari'],
                ]],
                ['2.3', 'Tersedia data dan informasi mengenai objek pemajuan kebudayaan Nagari', 'budaya', [
                    ['2.3.1', 'Tersedianya data dan informasi mengenai objek pemajuan kebudayaan Nagari'],
                ]],
                ['2.4', 'Tersedia dokumentasi mengenai sejarah Nagari', 'budaya', [
                    ['2.4.1', 'Tersedianya dokumentasi mengenai sejarah Nagari'],
                ]],
                ['2.5', 'Meningkatnya persentase warga Nagari yang merasa nyaman dan aman tinggal di Nagari', 'budaya', [
                    ['2.5.1', 'Persentase rumah tangga yang setuju jika ada sekelompok orang dari agama lain yang melakukan kegiatan di lingkungan sekitar tempat tinggal dalam 1 (satu) tahun terakhir'],
                    ['2.5.2', 'Persentase rumah tangga yang setuju jika ada sekelompok orang dari suku dan/atau ras lain yang melakukan kegiatan di lingkungan sekitar tempat tinggal dalam 1 (satu) tahun terakhir'],
                    ['2.5.3', 'Persentase rumah tangga yang setuju jika salah satu anggota rumah tangga berteman dengan orang lain yang beda agama dalam 1 (satu) tahun terakhir'],
                    ['2.5.4', 'Persentase rumah tangga yang setuju jika salah satu anggota rumah tangga berteman dengan orang lain yang berbeda suku dan/atau ras dalam 1 (satu) tahun terakhir'],
                    ['2.5.5', 'Persentase penduduk usia 10 (sepuluh) tahun ke atas yang mengikuti kegiatan sosial kemasyarakatan di lingkungan sekitar dalam 3 (tiga) bulan terakhir'],
                    ['2.5.6', 'Persentase penduduk usia 10 (sepuluh) tahun ke atas yang mengikuti gotong royong dalam 3 (tiga) bulan terakhir'],
                    ['2.5.7', 'Persentase rumah tangga yang merasa khawatir dengan keamanan saat berjalan kaki sendirian di malam hari dalam 1 (satu) tahun terakhir'],
                    ['2.5.8', 'Persentase rumah tangga yang merasa percaya menitipkan rumah kepada tetangga dalam 1 (satu) tahun terakhir'],
                ]],
                ['2.6', 'Meningkatnya persentase objek pemajuan kebudayaan yang dilestarikan', 'budaya', [
                    ['2.6.1', 'Persentase benda, bangunan, struktur, situs dan kawasan cagar budaya di Nagari yang telah dilindungi oleh Nagari dengan peraturan Nagari dan/atau peraturan kepala Nagari dalam 1 (satu) tahun terakhir'],
                    ['2.6.2', 'Persentase warisan budaya tak benda yang telah dilindungi oleh Nagari dengan peraturan Nagari dan/atau peraturan kepala Nagari dalam 1 (satu) tahun terakhir'],
                    ['2.6.3', 'Persentase penduduk usia 5 (lima) tahun ke atas yang menggunakan bahasa daerah di rumah dan/atau dalam pergaulan sehari-hari dalam 1 (satu) tahun terakhir'],
                    ['2.6.4', 'Persentase penduduk usia 10 (sepuluh) tahun ke atas yang menonton secara langsung pertunjukkan seni dalam 3 (tiga) bulan terakhir'],
                ]],
                ['2.7', 'Meningkatnya persentase objek pemajuan kebudayaan yang dikembangkan', 'budaya', [
                    ['2.7.1', 'Persentase objek pemajuan kebudayaan yang dikembangkan melalui program dan/atau kegiatan Pembangunan Nagari dalam 1 (satu) tahun terakhir'],
                    ['2.7.2', 'Persentase produk tradisional yang dikembangkan dengan mendayagunakan teknologi tepat guna dalam 1 (satu) tahun terakhir'],
                ]],
                ['2.8', 'Meningkatnya persentase objek pemajuan kebudayaan yang dimanfaatkan', 'budaya', [
                    ['2.8.1', 'Persentase rumah tangga yang menggunakan produk tradisional dalam 3 (tiga) bulan terakhir'],
                    ['2.8.2', 'Persentase rumah tangga yang menggunakan produk hasil inovasi di Nagari dalam 3 (tiga) bulan terakhir'],
                ]],
                ['2.9', 'Meningkatnya persentase warga Nagari yang memperoleh penghasilan dari kegiatan kebudayaan di Nagari', 'budaya', [
                    ['2.9.1', 'Persentase warga Nagari berusia 15 (lima belas) tahun ke atas yang pernah terlibat sebagai pelaku dan/atau pendukung pertunjukkan seni yang menjadikan keterlibatannya itu sebagai sumber penghasilan dalam 1 (satu) tahun terakhir'],
                ]],
                ['2.10', 'Meningkatnya persentase warga Nagari yang mengikuti pembelajaran kebudayaan Nagari yang diselenggarakan oleh Nagari', 'budaya', [
                    ['2.10.1', 'Jumlah pelatihan dan/atau pembelajaran mengenai kebudayaan Nagari yang diselenggarakan oleh Nagari dalam 1 (satu) tahun terakhir'],
                ]],
            ],
        ];
    }
}
