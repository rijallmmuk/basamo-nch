# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya, bukan untuk manusia.
> Maksimal 50 baris. Update di akhir setiap sesi kerja.
> Referensi task detail → TASKS.md

---

## Status proyek saat ini

**Fase**: MVP — LMS, panel /admin
**Milestone aktif**: Milestone 2 — LMS (ModuleResource + QuizResource selesai)
**Progres keseluruhan**: ~65%

---

## Sesi terakhir (2026-06-13)

**Dikerjakan**: QuizResource lengkap (Milestone 2.2 sebagian)
**Selesai**:
- [x] `QuizResource` — form (module_id, title, passing_score, max_attempts), table dengan questions_count badge
- [x] `QuestionsRelationManager` — form soal dengan Repeater opsi pilihan ganda (conditional visible type=multiple_choice), reorderable table
- [x] Shield generate ulang → 36 permissions, semua di-assign ke super_admin
- [x] Commit + push ke GitHub (f30d3a0)

**Blocker**: Tidak ada

---

## Mulai dari sini di sesi berikutnya

**Milestone 2.2 sisa — Essay grading:**
- [ ] Antrian penilaian essay untuk Admin Nagari (tampilan daftar attempt status `pending_review`)
- [ ] Form beri nilai + feedback untuk jawaban essay

**Milestone 2.3 — Portal warga (LAPISAN 2, CUSTOM Blade):**
1. Setup auth Laravel untuk warga: route login/register, middleware `role` cek warga/umkm_owner
2. Layout portal Blade (header, nav, mobile-first Tailwind)
3. Controller + halaman daftar modul dengan status (terkunci/tersedia/selesai)
4. Halaman detail modul + navigasi halaman per halaman
5. Tampil materi: teks (HTML), PDF embed, video embed
6. Livewire QuizPlayer: pilihan ganda auto-grade + essay submit

---

## Keputusan aktif & pola Filament v5

- `Schema $schema` (bukan Form/Infolist v3)
- `getNavigationGroup()` method (bukan property `?string` — type conflict di PHP 8.4)
- `define_via_gate = true` di filament-shield.php — super_admin bypass Gate::before()
- Setiap Resource baru: jalankan `shield:generate --all --panel=admin` + assign ke super_admin
- Thumbnail modul: FileUpload biasa ke `public/modules/thumbnails` (SpatieMedia nanti)
- Super admin: `admin@basamo.nch` / `password`
- Admin nagari: `admin.nagari@basamo.nch` / `password`
