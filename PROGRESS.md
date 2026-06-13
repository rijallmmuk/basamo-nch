# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya, bukan untuk manusia.
> Maksimal 50 baris. Update di akhir setiap sesi kerja.
> Referensi task detail → TASKS.md

---

## Status proyek saat ini

**Fase**: MVP — fokus LMS dulu, panel /admin dulu
**Milestone aktif**: Milestone 1 selesai → Milestone 2 (LMS — ModuleResource Filament)
**Progres keseluruhan**: ~40% (fondasi + DB + models selesai semua)

---

## Sesi terakhir (2026-06-13)

**Dikerjakan**: Milestone 1.3 — semua migration + model + policy + seeder LMS
**Selesai**:
- [x] Migration: nagaris, users FK, modules, module_pages, quizzes, quiz_questions, quiz_options, quiz_attempts, quiz_answers, user_module_progress, discussions
- [x] Eloquent Models dengan relasi lengkap untuk semua tabel LMS
- [x] User model ditambah relasi: nagari, moduleProgress, quizAttempts, discussions
- [x] ModulePolicy + QuizPolicy (scaffolded)
- [x] Seeder: NagariSeeder (NCH-001) + UserSeeder (super_admin + nagari_admin)
- [x] 4 role terdefinisi: super_admin, nagari_admin, warga, umkm_owner
- [x] Semua commit + push ke GitHub

**Blocker**: Tidak ada

---

## Mulai dari sini di sesi berikutnya

**Milestone 2.1 — ModuleResource (Admin Filament)**
1. Buat `ModuleResource` dengan `php artisan make:filament-resource Module --generate`
2. Sesuaikan form: title, slug (auto), description (RichEditor), thumbnail (SpatieMediaLibrary), status, nagari_id, prerequisite_module_id, order
3. Buat `ModulePageResource` atau gunakan RelationManager di dalam ModuleResource
4. Test CRUD modul di /admin

**Urutan setelah itu:**
- ModulePageResource (RelationManager di ModuleResource)
- QuizResource + QuizQuestion sebagai RelationManager
- Baru portal warga (Milestone 2.3)

---

## Strategi yang disepakati

- 3 lapisan: Frontend Publik / Portal Warga (Blade+Livewire) / Panel Admin (Filament)
- Fokus LMS & /admin dulu — portal warga menyusul setelah data LMS ada
- Schema: `Schema $schema` (bukan `Form`/`Infolist` ala Filament v3)
- Editor modul: RichEditor bawaan Filament (BUKAN Tiptap)
- Super admin: `admin@basamo.nch` / `password`
- Admin nagari: `admin.nagari@basamo.nch` / `password`

---

## Keputusan aktif

- activitylog v4.12 (v5 butuh PHP ^8.4)
- Module slug: auto via spatie/laravel-sluggable (HasSlug trait sudah di Module model)
- nagari_id FK di users: nullOnDelete (super_admin boleh tanpa nagari)
- Seeder idempoten: pakai firstOrCreate (aman di-run ulang)
