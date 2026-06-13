# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya, bukan untuk manusia.
> Maksimal 50 baris. Update di akhir setiap sesi kerja.
> Referensi task detail → TASKS.md

---

## Status proyek saat ini

**Fase**: MVP — LMS, panel /admin
**Milestone aktif**: Milestone 2.3 — Portal Warga (Blade + Livewire)
**Progres keseluruhan**: ~70%

---

## Sesi terakhir (2026-06-13)

**Dikerjakan**: Milestone 2.2 selesai penuh
**Selesai**:
- [x] `QuizAttemptResource` — list ujian, filter default pending_review, buka halaman review
- [x] `ReviewQuizAttempt` — halaman review (EditRecord read-only, tanpa tombol Save)
- [x] `AnswersRelationManager` — tabel jawaban peserta, EditAction hanya untuk essay
- [x] `LmsEssayGradingService` — hitung skor akhir (MC + essay), update attempt status
- [x] Tombol "Selesai Review" di header — validasi essay belum dinilai + finalize
- [x] Shield: 48 permissions, semua di-assign ke super_admin
- [x] Commit + push ke GitHub (003edb0)

**Blocker**: Tidak ada

---

## Mulai dari sini di sesi berikutnya

**Milestone 2.3 — Portal Warga (LAPISAN 2, CUSTOM Blade + Livewire):**
> Bukan panel Filament. Controller + Blade + Livewire. Auth Laravel standar.
1. Setup auth warga: route login/register, session, middleware `role` cek warga/umkm_owner
2. Layout portal Blade (header, nav, mobile-first Tailwind)
3. Controller + halaman daftar modul dengan status (terkunci/tersedia/selesai per user)
4. Halaman detail modul + navigasi halaman per halaman + update UserModuleProgress
5. Tampil materi: teks (HTML render), PDF embed, video YouTube/GDrive embed
6. Livewire QuizPlayer: pilihan ganda auto-grade
7. Livewire QuizPlayer: essay submit → create QuizAttempt + QuizAnswer pending_review

---

## Keputusan aktif & pola Filament v5

- `Schema $schema` (bukan Form/Infolist v3)
- `getNavigationGroup()` method (bukan property `?string` — type conflict di PHP 8.4)
- `define_via_gate = true` di filament-shield.php — super_admin bypass Gate::before()
- Setiap Resource baru: jalankan `shield:generate --all --panel=admin` + assign ke super_admin
- Super admin: `admin@basamo.nch` / `password`
- Admin nagari: `admin.nagari@basamo.nch` / `password`
- `LmsEssayGradingService::finalize()` — logic kalkulasi nilai akhir ujian
