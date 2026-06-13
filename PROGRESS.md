# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya, bukan untuk manusia.
> Maksimal 50 baris. Update di akhir setiap sesi kerja.
> Referensi task detail → TASKS.md

---

## Status proyek saat ini

**Fase**: MVP — LMS, panel /admin
**Milestone aktif**: Milestone 2 — LMS (ModuleResource + Pages selesai, lanjut Quiz)
**Progres keseluruhan**: ~55%

---

## Sesi terakhir (2026-06-13)

**Dikerjakan**: Fix Shield + PagesRelationManager
**Selesai**:
- [x] Fix menu LMS tidak muncul: `define_via_gate=true`, hapus ModulePolicy yang salah, regenerate Shield (24 permissions), assign ke super_admin
- [x] `PagesRelationManager` — form conditional by type (text/video/pdf), drag & drop reorder, badge tipe berwarna
- [x] Commit + push semua perubahan

**Blocker**: Tidak ada

---

## Mulai dari sini di sesi berikutnya

**Milestone 2.2 — QuizResource**
1. `php artisan make:filament-resource Quiz --generate`
2. Form Quiz: title, passing_score, max_attempts (terkait module via RelationManager di ModuleResource)
3. `QuizQuestionsRelationManager` di QuizResource:
   - Form: question (text), type (multiple_choice/essay), points, order
   - Sub-RelationManager: QuizOptions di dalam QuizQuestion (nested, atau gunakan Repeater)
4. Pertimbangkan: opsi jawaban (quiz_options) lebih baik pakai **Repeater** di dalam form soal (bukan nested relation manager) — tanyakan ke user

**Setelah Quiz:**
- Milestone 2.3: Portal warga (auth login + daftar modul)

---

## Keputusan aktif & pola Filament v5

- `Schema $schema` (bukan Form/Infolist v3)
- `getNavigationGroup()` method (bukan property `?string` — type conflict di PHP 8.4)
- `define_via_gate = true` di filament-shield.php — super_admin bypass Gate::before()
- ModulePolicy: dikelola Shield (`$authUser->can('ViewAny:Module')`)
- Setiap Resource baru: jalankan `shield:generate --all --panel=admin` + assign ke super_admin
- Thumbnail modul: FileUpload biasa ke `public/modules/thumbnails` (SpatieMedia nanti)
- Super admin: `admin@basamo.nch` / `password`
- Admin nagari: `admin.nagari@basamo.nch` / `password`
