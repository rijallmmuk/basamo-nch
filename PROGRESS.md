# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya, bukan untuk manusia.
> Maksimal 60 baris. Update di akhir setiap sesi kerja.
> Referensi task detail → TASKS.md

---

## Status proyek saat ini

**Fase**: MVP — LMS, panel /admin
**Milestone aktif**: 2.4 Poin & Leaderboard + 2.6 Lapak/Kerangka halaman
**Progres keseluruhan**: ~88%

---

## Sesi terakhir (2026-06-14) — Forum Diskusi (2.5) + Redesign UI LMS

**Selesai sesi ini:**
- [x] Forum Diskusi (2.5): `DiscussionController` (index/show/store/reply, scope nagari penulis), routes `portal.modules.discuss[.store|.show|.reply]`, view index+thread, CTA di show
- [x] Redesign UI LMS menyeluruh (bahasa desain konsisten): Beranda, daftar modul, detail modul, baca materi, kuis, diskusi
- [x] Komponen Blade reusable baru di `components/portal/`: `avatar`, `status-badge`, `content-badge`, `empty` (pakai `x-dynamic-component` utk heroicon)
- [x] Layout: header backdrop-blur + avatar, bottom nav `fixed` (2 item), bg slate-50
- [x] Quiz player: progress "X dari Y soal terjawab" (live), highlight pilihan live (`wire:model.live`), auto-scroll ke error
- [x] Verifikasi: view:cache OK, `npm run build` OK, smoke test HTTP login warga → 6 halaman + thread semua HTTP 200 tanpa error

**Keputusan desain (UI/UX, full ownership):** primary indigo, netral slate; kartu `rounded-2xl`, tombol/input `rounded-xl`; status semantik (emerald=selesai, indigo=berjalan, sky=baru, amber=menunggu, red=error); Heroicons saja (emoji jenis-materi diganti badge heroicon).

**Catatan**: UI overhaul (2026-06-13) + Beranda + Diskusi + redesign ini semua masih uncommitted — minta user konfirmasi sebelum commit.

**Sesi sebelumnya (2026-06-13) — UI Overhaul + Beranda:**
- Redesign portal: sidebar → bottom nav (2 item: Beranda, Modul); header brand+dropdown
- Module cards `lg:grid-cols-3`, font text-base, ikon `<x-heroicon-*>`
- Beranda: HomeController + `portal/home.blade.php` (hero sapaan + Aktivitas Belajar 3 modul)
- Bug fixes: `route('portal.modules.show')` di QuizPlayer, page ownership check, pending_review di max_attempts, module published/nagari check di semua controller

---

## Yang akan dikerjakan BERIKUTNYA (tunggu sinyal "lanjut")

### PRIORITAS 1 — Sistem Poin & Leaderboard (Milestone 2.4)
- Service `LmsPointService`: kalkulasi poin per aktivitas (halaman selesai, kuis lulus)
- Update `users.total_points` otomatis saat halaman selesai / kuis lulus
- Halaman leaderboard portal (ranking per nagari; pakai pages_completed dulu → total_points)

### PRIORITAS 1b — UX polish LMS (perbaikan kecil)
- Layar "Selesai! Semua materi tuntas" saat halaman terakhir selesai — sebelum CTA kuis
- Quiz player: progress "Soal X dari Y" + indikator jawaban + scroll ke soal kosong saat validasi gagal

### PRIORITAS 2 — Kerangka halaman (front-end sample saja, konten placeholder)
> Dikerjakan SETELAH LMS selesai
- Beranda (dashboard): hero sapaan + preview modul in_progress + top 5 leaderboard + CTA UMKM
- Leaderboard: ranking nagari berdasarkan pages_completed (upgrade ke total_points saat M2.4)
- Lapak UMKM: info cara daftar (warga) / placeholder (umkm_owner)
- Bottom nav: update jadi 4 item aktif (Beranda, Modul, Leaderboard, Lapak)

---

## Keputusan teknis aktif

- `Schema $schema` (Filament v5, bukan Form v3)
- `getNavigationGroup()` method (PHP 8.4 type invariance)
- `define_via_gate = true` di filament-shield — super_admin bypass via Gate::before()
- Setiap Resource baru: `shield:generate --all --panel=admin` + assign ke super_admin
- `$quizErrors` di QuizPlayer (BUKAN `$errors` — reserved Livewire)
- Bottom nav: `@section('bottom-navigation') ... @show` di layout, child view override untuk sembunyikan (page.blade.php)
- Nagari `status = 'active'` (bukan 'aktif')
- Super admin: `admin@basamo.nch` / `password`
