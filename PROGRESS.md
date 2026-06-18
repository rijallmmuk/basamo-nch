# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya. Ringkas (≤60 baris).
> Detail task → TASKS.md · Keputusan → DECISIONS.md
> Aturan: baca CLAUDE.md + PROGRESS.md + TASKS.md di awal sesi.

---

## Status

**Fase**: MVP — LMS (portal warga + admin) hampir lengkap.
**Progres**: ~92%. Git bersih & ter-push ke origin/main.
**Login uji**: super_admin username `admin` atau email `admin@basamo.nch` / `password` (login admin terima username **atau** email). Warga uji: `rijal@mail.com` (password tak diketahui — pakai reset bila perlu).

---

## ⏭️ BERIKUTNYA (saat user ketik "lanjut")
> Konfirmasi arah dulu ke user, lalu kerjakan. Kandidat (urut saran):
1. **Data demo/seeder** — XP & leaderboard sekarang masih 0; seed warga + progres + XP agar showcase terlihat hidup.
2. **Lapak UMKM** (2.6) — halaman placeholder + menu "Produk Saya" (khusus `umkm_owner`). Belum dibangun.
3. **Milestone berikutnya** — UMKM (M4) / SDGs (M3) / Dashboard admin (M5).
4. **Testing Pest** — coverage portal/admin masih minim.

---

## Yang sudah jadi (LMS)

**Admin (Filament /admin):** CRUD Modul (auto-order, drag, slug stabil, tanpa thumbnail, scoping nagari), CRUD Kuis (MC-only, nilai 0–100 tanpa %, 1 modul=1 kuis, validasi 1 jawaban benar, max_attempts 0=tak terbatas), Materi (teks/PDF disk public maks 10MB/video YouTube+GDrive). Menu Role disembunyikan.

**Portal warga:** Shell = sidebar (desktop) + bottom-nav (mobile) + top header (lonceng notifikasi + dropdown user). Dashboard (hero progres, kartu Modul Selesai/XP/Peringkat, Lanjutkan Belajar, Peringkat XP Top 5). Daftar/detail modul, baca materi, QuizPlayer (confetti+toast saat lulus), Diskusi per modul, Notifikasi in-app, Leaderboard XP per nagari.

**XP** (idempotent via `xp_logs`): modul selesai +50, lulus kuis +100, diskusi (posting pertama/modul) +20 → `users.total_points`. `LmsPointService`.

**UI kit** (`components/portal/`): avatar, status-badge, content-badge, empty, breadcrumb, button, card, badge, progress, stat, toast. Dependency: `canvas-confetti`.
Adopsi: `card` (panel) dipakai di home/leaderboard/modules show+page; `stat` di dashboard (hapus duplikasi); `button` di form diskusi. Sengaja DILEWATI (tak memetakan bersih ke 4 varian/risiko regresi): CTA modul berkondisi completed/in_progress (modules index+show), reader-nav page (varian emerald/border), kartu padded dalam loop & form (discuss reply/notif).

---

## Audit super_admin (2026-06-18) — SELESAI fondasi
- RBAC dirombak: **kolom `role` = sumber kebenaran tunggal**. super_admin via `Gate::before` (cek kolom); policy berbasis role; observer sinkron Spatie role saat kolom berubah.
- Fix: B2 (admin nonaktif diblokir di `canAccessPanel`), H1 (nagari_admin tak lagi panel kosong), L2 (scoping route-binding QuizResource), L1 (`modules.created_by` nullable+nullOnDelete).
- Test: `tests/Feature/SuperAdminAccessTest.php` (6 lulus). Factory dapat state role: `superAdmin()/nagariAdmin()/warga()/umkmOwner()/inactive()`.
- **MASIH KURANG (didiskusikan, ditunda):** `NagariResource` (M1) & `UserResource` (M2) — super_admin belum bisa kelola nagari/user via UI. Dashboard super_admin masih kosong (M5). `FilamentInfoWidget` (promo) sebaiknya dibuang utk produksi.
- Catatan pre-existing: `tests/Feature/ExampleTest` gagal (uji `/` = 200 tapi app redirect 302) — bukan dari perubahan ini.

## Keputusan teknis aktif (detail di DECISIONS.md)
- Kuis MC-only; nilai angka 0–100 (bukan %); tanpa bobot poin per soal.
- Scoping nagari admin manual (bukan Filament Tenancy). super_admin kelola global.
- Leaderboard berbasis XP pencapaian (sempat dihapus, lalu dihidupkan lagi).
- UI: komponen Blade sendiri (bukan Flux/WireUI) + canvas-confetti.
- PDF materi: disk `public` + symlink; PHP `upload_max_filesize=10M`/`post_max_size=12M` (set di server; lihat DECISIONS).
- Filament v5: `Schema $schema`; shield define_via_gate; super_admin via Gate::before.
