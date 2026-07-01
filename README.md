# Basamo NCH — Smart Learning Center

Platform digital 4 pilar untuk nagari: LMS · SDGs · UMKM · IoT Sensor.
Program Nagari Creative Hub (NCH) — Gubernur Sumatera Barat.
Fase saat ini: MVP — fokus LMS dulu.

---

## Stack

Laravel 13 · PHP 8.4 · Filament v5 (panel admin) + portal custom · MySQL · Tailwind · ApexCharts.
Auth: Filament bawaan (tanpa Jetstream). RBAC: Filament Shield + Spatie Permission.
Editor: RichEditor bawaan Filament. Vibe coding: Claude Code.

**Arsitektur 3 lapisan (satu Laravel, satu MySQL):**
1. Frontend Publik (Blade) — halaman utama + katalog UMKM, tanpa login
2. Portal Warga LMS (Blade + Livewire) — belajar/kuis, bukan Filament
3. Panel Admin (Filament `/admin`) — kelola modul, SDGs, verifikasi UMKM

---

## File dokumen

| Dokumen | Isi |
|---|---|
| `CLAUDE.md` | Konteks utama — dibaca Claude Code otomatis tiap sesi |
| `TASKS.md` | Semua task per milestone dengan status |
| `PROGRESS.md` | Status sesi terakhir & instruksi sesi berikutnya |
| `PLANNING.md` | Arsitektur sistem & struktur direktori |
| `DECISIONS.md` | Log keputusan teknis (termasuk revisi) |
| `CONVENTIONS.md` | Coding style, naming, git workflow |
| `INSTALL_COMMANDS.md` | Semua perintah install dependency |
| `docs/PRD.md` | Spesifikasi produk lengkap |
| `docs/DATABASE.md` | Skema 16 tabel + relasi |
| `docs/UI-GUIDE.md` | Panduan warna, komponen, Tailwind |

---

## Status setup saat ini

Laravel, Filament v5, dan sebagian besar dependency **sudah terpasang**.
Untuk dependency yang belum + langkah konfigurasi, lihat `INSTALL_COMMANDS.md`.

Yang masih perlu dikerjakan ada di `PROGRESS.md` bagian "Mulai dari sini".

---

## Cara pakai dengan Claude Code

### Memulai sesi
Claude Code otomatis membaca `CLAUDE.md`. Cukup ketik salah satu:
- `/start-session` — agent baca PROGRESS + TASKS, lapor status, tanya mau kerjakan apa
- `/check-progress` — ringkasan progres semua milestone
- `/new-feature` — workflow tambah fitur baru (migration → model → service → resource → test)
- `/end-session` — update PROGRESS + TASKS, format kode, commit, push

Command tersedia di folder `.claude/commands/`.

### Tanpa custom command
Cukup minta: "Baca CLAUDE.md, PROGRESS.md, dan TASKS.md. Lapor status proyek
dan task yang siap dikerjakan."

---

## Commands referensi cepat

```bash
php artisan serve                   # jalankan server
npm run dev                         # compile assets
php artisan migrate:fresh --seed    # reset DB + data dummy
php artisan test                    # jalankan test
./vendor/bin/pint                   # format kode
php artisan make:filament-resource NamaModel --generate
```

---

## Deploy produksi — catatan wajib

**Queue worker WAJIB berjalan.** `QUEUE_CONNECTION=database`, dan notifikasi in-app
(modul baru, kuis baru, verifikasi produk UMKM) memakai `ShouldQueue`. Tanpa worker,
notifikasi **tidak akan terkirim** (job menumpuk di tabel `jobs`, tak pernah diproses).

Jalankan worker sebagai proses persisten (Supervisor/systemd), bukan sekali jalan:

```bash
php artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
```

Contoh Supervisor (`/etc/supervisor/conf.d/basamo-worker.conf`):

```ini
[program:basamo-worker]
command=php /path/ke/basamo-nch/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/ke/basamo-nch/storage/logs/worker.log
stopwaitsecs=3600
```

> Setelah tiap deploy yang mengubah kode, restart worker: `php artisan queue:restart`
> (worker lama memuat kode basi sampai di-restart).

---

## Milestone ringkas

| Milestone | Fokus | Target |
|---|---|---|
| 1 | Setup, auth, RBAC, migration & model | Minggu 1–2 |
| 2 | LMS: modul, kuis, poin, leaderboard, forum | Minggu 3–5 |
| 3 | SDGs: 18 poin, dokumen, visualisasi | Minggu 6–7 |
| 4 | UMKM: profil, produk, verifikasi, katalog | Minggu 8–9 |
| 5 | Dashboard analitik, IoT simulasi | Minggu 10–11 |
| 6 | Data dummy, ekspor PDF & Excel | Minggu 12 |
| 7 | Polish, testing, security audit | Minggu 13–14 |

Detail → `TASKS.md`
