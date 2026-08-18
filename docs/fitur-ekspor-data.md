# Fitur Ekspor Data

Seluruh ekspor memakai query yang sudah dibatasi oleh Resource/Policy, konteks nagari, pencarian, filter, dan urutan tabel. Jangan mengganti query laporan dengan query model global tanpa menerapkan kembali batas akses tersebut.

## Standar keluaran

- Excel dihasilkan secara streaming agar daftar besar tidak ditarik sekaligus ke memori.
- Excel memiliki metadata pembuat/cakupan, header, filter otomatis, freeze pane, lebar kolom, dan nama file bertanggal.
- PDF memakai A4, identitas platform, waktu dan pembuat laporan, cakupan, jumlah data, tabel berulang, serta nomor halaman.
- Ekspor sensitif dicatat pada log `ekspor` setelah file berhasil dibangun.
- DPMD tidak mendapat ekspor identitas mentah warga; aksesnya berupa laporan demografi agregat.
- Operator selalu dibatasi ke nagarinya, termasuk pada pelatihan lintas nagari.
- Warga dan pemilik UMKM hanya memperoleh data miliknya sendiri.

## Penempatan

| Area | Ekspor |
|---|---|
| Warga | Excel operasional, Excel impor ulang, demografi Excel/PDF |
| Rekap Belajar | Rekap Excel/PDF dan transkrip PDF individual |
| Pelatihan | Daftar, rekap peserta/nilai/sertifikat, dan kehadiran Excel/PDF |
| Modul dan Evaluasi | Excel struktur/kesiapan konten |
| UMKM | Profil Excel/PDF, produk Excel, analitik 30 hari Excel/PDF |
| Nagari | Ringkasan lintas nagari Excel dan profil nagari PDF |
| SDGs dan IDM | Excel/PDF per nagari terpilih |
| EWS | Excel/PDF dengan periode 7, 30, 90, atau 365 hari |
| Sistem | Pengguna back-office, pesan masuk, dan log aktivitas dalam Excel |
| Portal warga | Data pribadi PDF dan transkrip belajar PDF |

## Komponen utama

- `ReportExporter`: mesin Excel/PDF tabel.
- `TabularReport` dan `ReportColumn`: definisi dataset dan kolom.
- `ExportsTableReports`: integrasi daftar Filament dengan query filter aktif.
- Exporter khusus dipakai untuk dokumen yang bukan tabel biasa: demografi, profil nagari, data pribadi, dan transkrip belajar.

## Pengujian

`tests/Feature/Reports/ReportExporterTest.php` memvalidasi struktur XLSX dengan membacanya kembali menggunakan OpenSpout, signature PDF, query scope, audit, multi-sheet demografi, dan otorisasi unduhan warga.

Smoke test browser tersedia pada `tests/Browser/export-smoke.mjs`. Fixture-nya dibuat oleh
`tests/Browser/prepare-export-smoke.php` pada database SQLite sementara. Pengujian ini
masuk melalui halaman login sebagai superadmin, operator, pengajar, DPMD, warga, dan
pemilik UMKM; menekan aksi ekspor nyata; lalu memvalidasi signature setiap berkas yang
diunduh Chromium. Jangan menjalankan fixture terhadap database pengembangan atau produksi.
