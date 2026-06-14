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

## Sesi terakhir (2026-06-14g) — Audit CRUD Quiz (super admin)

- Cek menyeluruh: QuizForm, QuizzesTable, Create/EditQuiz, QuestionsRelationManager, cascade, model.
- Fix: Repeater pilihan jawaban pakai `orderColumn('order')` (sebelumnya `reorderable()` tanpa orderColumn → drag tak tersimpan & order opsi tak ter-assign).
- Diverifikasi aman: filter modul (1 modul=1 kuis, sertakan sendiri saat edit), validasi "tepat satu jawaban benar", max_attempts 0=tak terbatas, nilai 0–100, reorder soal (table reorderable + auto-order), EditQuiz punya Delete, cascade hapus kuis→soal/opsi/attempt.
- Catatan: hapus kuis bersifat permanen (Quiz tanpa SoftDeletes) — wajar sebagai anak Modul.

---

## Sesi 2026-06-14f — Audit CRUD Modul (super admin)

- Cek menyeluruh CRUD modul: form, tabel, create/edit, PagesRelationManager, observer, soft-delete.
- Fix: slug modul kini **stabil saat judul diedit** (`doNotGenerateSlugsOnUpdate()`) → URL tidak putus, field slug readOnly konsisten dgn yang tersimpan.
- Diverifikasi aman: judul duplikat → slug auto-suffix; auto-order; scoping nagari; created_by tetap saat edit; soft-deleted module tak muncul di portal.
- Catatan minor (sengaja TIDAK diubah, low-risk): file PDF lama tak terhapus otomatis saat page diganti/dihapus; data field tersembunyi (saat ganti tipe halaman) tertinggal tapi tak ditampilkan.

---

## Sesi 2026-06-14e — Nilai kuis: angka 0–100, soal setara

- Tanda `%` dihapus dari semua nilai kuis (admin form/tabel, portal hasil/header, notifikasi) — nilai = angka 0–100. (Progress "% soal terjawab" & "% materi" tetap, itu progres.)
- Bobot poin per soal **dihapus**: kolom `quiz_questions.points` & `quiz_answers.score_given` di-drop (migration `drop_points_from_quiz`)
- Skoring: `nilai = (jumlah benar ÷ jumlah soal) × 100` di QuizPlayer; form/tabel soal tak ada lagi field/kolom Poin
- Dampak: nilai selalu 0–100, ambang lulus selalu mungkin dicapai (human-error "passing > maks" mustahil)
- Docs: DATABASE.md, PRD.md, DECISIONS.md diperbarui
- Verifikasi: migrate OK (kolom gone), pint OK, view:cache OK, admin boot OK, tak ada sisa referensi points

---

## Sesi 2026-06-14d — Penyesuaian dari feedback

- Kuis `max_attempts` boleh **0 = tidak terbatas** (`minValue(0)`, maxValue 255)
- Form opsi: helperText dihapus; pesan "tandai tepat satu benar" hanya muncul saat validasi gagal
- **Notifikasi kuis baru**: `NewQuizPublished` + `QuizObserver` (saat kuis dibuat di modul published)
- **Auto-urut** materi & soal (`booted creating` di ModulePage/QuizQuestion) → kolom `#` tak lagi 0; data lama di-renumber
- **Urutan modul**: auto (max+1 via ModuleObserver) + tabel modul `reorderable('order')` drag; field angka di form dihapus; data lama di-renumber
- **Menu Role** disembunyikan dari navigasi: `FilamentShieldPlugin::make()->registerNavigation(false)`
- Verifikasi: pint OK, panel boot OK, auto-order & notifikasi kuis diuji via transaksi rollback (bersih)

---

## Sesi 2026-06-14c — Audit LMS + scoping nagari + notifikasi + leaderboard dummy

**Audit CRUD admin & portal — bug diperbaiki:**
- PDF materi rusak (disk `local` privat + symlink hilang) → `storage:link`, FileUpload `->disk('public')`, portal pakai `Storage::disk('public')->url()`
- Kuis duplikat per modul → `QuizForm` filter `whereDoesntHave('quiz')` (1 modul = 1 kuis), tetap sertakan modul sendiri saat edit
- Urutan materi/soal tak deterministik → tiebreaker `->orderBy('id')` di `pages()`, `questions()`, `options()`
- Tidak ada validasi 1 jawaban benar → rule "tepat satu is_correct" di Repeater opsi
- Video Google Drive kini ter-embed (selain YouTube) di portal

**Scoping nagari admin (manual, bukan Filament Tenancy):**
- `User::isSuperAdmin()/isNagariAdmin()`
- `ModuleResource` (getEloquentQuery + getRecordRouteBindingEloquentQuery) & `QuizResource` scope ke nagari sendiri utk nagari_admin
- `ModuleForm`: field nagari hanya untuk super_admin; `CreateModule` isi nagari_id otomatis; prasyarat & pilihan modul kuis dibatasi per-nagari
- Verifikasi: super_admin lihat semua, nagari_admin hanya nagarinya

**Notifikasi in-app (2.3):** tabel notifications, `NewModulePublished` (ModuleObserver saat publish) + `QuizCompleted` (QuizPlayer), `NotificationController` + halaman + lonceng badge di header. Observer diuji via transaksi rollback (kirim OK, tanpa sisa data).

**Leaderboard (2.4):** DUMMY front-end (`LeaderboardController` + view, banner "data contoh"); sistem poin nyata TERTUNDA menunggu kesepakatan penilaian. Bottom nav → 3 item (Beranda, Modul, Peringkat).

**Verifikasi:** pint OK, view:cache OK, `npm run build` OK, semua 7 halaman portal HTTP 200 (login warga, password dipulihkan), panel admin boot OK, logika query form admin valid.

**Catatan:** belum di-commit. Sistem poin & leaderboard nyata menunggu diskusi skema penilaian.

---

## Sesi 2026-06-14b — Kuis MC-only + hapus thumbnail

**Selesai sesi ini:**
- [x] Kuis ditetapkan **hanya pilihan ganda** (auto-grade). Tipe essay dihapus total.
- [x] Hapus antrian review essay: QuizAttemptResource + ListQuizAttempts + ReviewQuizAttempt + QuizAttemptInfoSchema + QuizAttemptsTable + AnswersRelationManager + LmsEssayGradingService
- [x] QuestionsRelationManager: hapus pilihan tipe soal, kolom tabel → jumlah pilihan
- [x] Modul tanpa thumbnail: hapus field di ModuleForm + ImageColumn di ModulesTable + tampilan di portal show
- [x] Portal: QuizPlayer & quiz-player.blade MC-only (hapus path pending_review & textarea essay); QuizController hapus pending_review dari hitungan percobaan
- [x] Model: bersihkan fillable/relasi (Module, QuizQuestion, QuizAnswer, QuizAttempt)
- [x] Migration `drop_essay_and_thumbnail_columns`: drop kolom + bersihkan data essay/pending_review (guard MySQL utk enum). Sudah di-migrate di MySQL dev.
- [x] Dokumentasi: DATABASE.md, PRD.md, DECISIONS.md (keputusan 2026-06), TASKS.md
- [x] Verifikasi MySQL: schema bersih, admin panel boot OK (route:list), /admin/quiz-attempts → 404, view:cache OK, pint OK

**Catatan**: perubahan sesi ini BELUM di-commit (menunggu konfirmasi). Permission Shield orphan utk QuizAttempt dibiarkan (tak berdampak).

---

## Sesi 2026-06-14a — Forum Diskusi (2.5) + Redesign UI LMS

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
