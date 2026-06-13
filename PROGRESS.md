# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya, bukan untuk manusia.
> Maksimal 50 baris. Update di akhir setiap sesi kerja.
> Referensi task detail → TASKS.md

---

## Status proyek saat ini

**Fase**: MVP — LMS, panel /admin
**Milestone aktif**: Milestone 2 — LMS (ModuleResource selesai, lanjut ModulePage)
**Progres keseluruhan**: ~50%

---

## Sesi terakhir (2026-06-13)

**Dikerjakan**: ModuleResource Filament v5
**Selesai**:
- [x] `ModuleResource` dengan navigation group 'LMS'
- [x] `ModuleForm`: title (auto-slug), nagari select, status, prerequisite, order, thumbnail upload, RichEditor
- [x] `ModulesTable`: badge status berwarna, nagari badge (fallback Global), filter status/nagari/trashed
- [x] Eager load `nagari` + `creator` di `getEloquentQuery()`
- [x] Filament v5 pattern: `Schema $schema`, method `getNavigationGroup()` (bukan property karena type conflict)
- [x] Commit + push

**Blocker**: Tidak ada

---

## Mulai dari sini di sesi berikutnya

**Milestone 2.1 lanjutan — ModulePages (RelationManager)**
1. Buat `ModulePagesRelationManager` di dalam ModuleResource
   - `php artisan make:filament-relation-manager ModuleResource pages title`
   - Form: title, type (select: text/pdf/video), content (RichEditor jika text), video_url (jika video), file_path upload (jika pdf), order
   - Table: title, type badge, order — sortable

**Milestone 2.2 — QuizResource**
2. Buat `QuizResource` + `QuizQuestionsRelationManager`
   - Soal multiple choice + essay
   - Options sebagai nested RelationManager

**Setelah itu:**
- Milestone 2.3 (Portal warga custom Blade) dimulai saat data LMS sudah bisa diinput

---

## Strategi & keputusan aktif

- Filament v5: `Schema $schema` (bukan Form/Infolist); `getNavigationGroup()` method (bukan property ?string)
- Thumbnail modul: saat ini FileUpload biasa ke `public/modules/thumbnails` — upgrade ke SpatieMedia nanti
- Auto-slug: `HasSlug` di model + `afterStateUpdated` di form untuk live preview
- `getEloquentQuery()` di Resource — tempat untuk eager load & scope nagari (nanti)
- Super admin: `admin@basamo.nch` / `password`
- Admin nagari: `admin.nagari@basamo.nch` / `password`
