# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya, bukan untuk manusia.
> Maksimal 50 baris. Update di akhir setiap sesi kerja.
> Referensi task detail → TASKS.md

---

## Status proyek saat ini

**Fase**: MVP — fokus LMS dulu, panel /admin dulu
**Milestone aktif**: Milestone 1 — Setup & Fondasi
**Progres keseluruhan**: ~25% (fondasi install selesai, panel /admin bisa diakses)

---

## Sesi terakhir (2026-06-13)

**Dikerjakan**: Eksekusi INSTALL_COMMANDS.md BLOK 1–12 penuh dari nol
**Selesai**:
- [x] Filament v5.6.7 + panel `/admin` terpasang
- [x] Shield v4.2, Spatie Permission v7.4, Media Library v11.23, Sluggable v4.0, ActivityLog v4.12, ApexCharts v5.1, Pint, Pest
- [x] Database `basamo_nch` dibuat, `.env` sudah benar
- [x] Migration dijalankan (users + cache + jobs + media + permission + activitylog)
- [x] Model `User` diupdate: `HasRoles`, `FilamentUser`, `canAccessPanel()`, `SoftDeletes`, fillable lengkap
- [x] Kolom `role`, `nagari_id`, `avatar`, `total_points`, `status`, `deleted_at` di tabel users
- [x] Shield di-install ke panel admin, permissions di-generate (12 permission)
- [x] Super admin dibuat: `admin@basamo.nch` / `password`
- [x] `/admin/login` → HTTP 200 ✓ (panel bisa diakses)

**Belum selesai**:
- Init Git + push GitHub
- Definisikan 4 role formal (sekarang baru super_admin via Shield)
- Migration LMS: nagaris, modules, module_pages, quizzes, dll (Milestone 1.3)

**Blocker**: Tidak ada

---

## Mulai dari sini di sesi berikutnya

1. Init Git repository (`git init`, buat `.gitignore` yang benar, push ke GitHub)
2. Kerjakan Milestone 1.3 — buat migration & model untuk semua tabel LMS:
   - `nagaris` → `users` (tambah FK nagari_id) → `modules` → `module_pages`
   - `quizzes` → `quiz_questions` → `quiz_options`
   - `quiz_attempts` → `quiz_answers` → `user_module_progress` → `discussions`
3. Buat Seeder: 1 nagari dummy + super admin + 1 nagari admin
4. Buat Filament Resource pertama: `ModuleResource` (Milestone 2.1)

---

## Strategi yang disepakati — ARSITEKTUR 3 LAPISAN

- **3 lapisan, satu Laravel, satu MySQL:**
  1. Frontend Publik (Blade) — home + katalog UMKM, tanpa login
  2. Portal Warga LMS (Blade + Livewire) — belajar/kuis, BUKAN Filament
  3. Panel Admin (Filament /admin) — super_admin & nagari_admin
- **Fokus LMS dulu**, **panel /admin dulu** — bangun CRUD modul/materi/kuis.
- Portal warga & frontend publik dibangun SETELAH fondasi data LMS jadi.

---

## Keputusan aktif yang perlu diingat

- Super admin: `admin@basamo.nch` / `password` (ubah sebelum deploy)
- TANPA Jetstream — auth Filament bawaan, 1 model User, pisah via canAccessPanel()
- Editor modul: RichEditor bawaan Filament (BUKAN Tiptap)
- activitylog terpasang v4.12 (v5 butuh PHP ^8.4, platform belum match)
- Tailwind: bawaan Filament untuk admin; custom Blade+Tailwind untuk portal & publik
- Icon: Heroicons saja — Storage MVP: local disk
