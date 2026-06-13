# AGENTS.md — Smart Learning Center Basamo NCH

> File utama yang dibaca semua AI IDE (Antigravity, Cursor, Windsurf, Copilot, dll).
> Dibaca otomatis setiap sesi. Berisi semua konteks, aturan, dan konvensi proyek.

---

## Proyek ini

Platform web 4 pilar untuk nagari di program Nagari Creative Hub (NCH) Gubernur Sumbar:

1. **LMS** — portal belajar literasi digital untuk warga nagari
2. **SDGs Desa** — entri & visualisasi 18 poin SDGs per nagari
3. **UMKM** — repositori & promosi produk UMKM lokal
4. **IoT** — monitoring sensor bencana real-time (simulasi di MVP)

**Fase saat ini**: MVP aktif — belum production.
**Skala**: pilot 3 nagari → nasional bertahap.

---

## Stack (facts, bukan asumsi)

- **Arsitektur**: 3 lapisan dalam satu Laravel — (1) Frontend Publik Blade, (2) Portal Warga Blade+Livewire, (3) Panel Admin Filament `/admin`
- **Backend**: Laravel + PHP (versi terbaru — saat ini Laravel 13, PHP 8.4)
- **Admin**: Filament v5 — satu panel `/admin` (AdminPanelProvider). Portal & publik BUKAN Filament
- **Auth**: Filament bawaan untuk admin (canAccessPanel cek role); auth Laravel + middleware untuk portal. TANPA Jetstream. Satu model User
- **DB**: MySQL — semua tabel punya `nagari_id` untuk multi-tenancy
- **Charts**: ApexCharts via `leandrocfe/filament-apex-charts`
- **Icons**: Heroicons saja — jangan tambah icon set lain
- **Editor modul**: RichEditor bawaan Filament — JANGAN pakai Tiptap (tidak support v5)
- **Notifikasi**: Filament Notifications bawaan (in-app) — jangan pakai Toastr/SweetAlert
- **Media**: Spatie Media Library + Filament plugin
- **Storage**: Local disk (MVP) → Cloudflare R2 (produksi)
- **CSS**: Tailwind utility classes — jangan pakai Bootstrap atau framework CSS lain
- **Vibe coding tool**: Claude Code — baca `CLAUDE.md` & gunakan `.claude/commands/`

---

## Sebelum menulis kode apapun

1. Baca `PROGRESS.md` — pahami state terkini proyek
2. Baca `TASKS.md` — identifikasi task yang aktif
3. Baca `docs/` yang relevan jika menyentuh domain tertentu
4. **Jangan mulai coding sebelum memahami konteks**

---

## 4 Role & 3 Lapisan

| Role | Lapisan | Akses |
|---|---|---|
| `super_admin` | Admin (Filament `/admin`) | Semua nagari, modul global |
| `nagari_admin` | Admin (Filament `/admin`) | Nagari sendiri (Tenancy) |
| `warga` | Portal (Blade custom) | LMS: belajar, kuis, leaderboard |
| `umkm_owner` | Portal (Blade custom) | LMS + input produk UMKM |
| (publik) | Frontend (Blade custom) | Katalog UMKM, profil nagari — tanpa login |

Catatan: Admin masuk panel Filament via `canAccessPanel()` (cek role super_admin/nagari_admin).
Warga & UMKM owner akses portal via auth Laravel + middleware role, BUKAN panel Filament.
Menu UMKM di portal hanya muncul untuk role `umkm_owner`.

Multi-tenancy via `Nagari` model. Admin nagari hanya lihat data `nagari_id` miliknya.

---

## Struktur direktori penting

```
app/
  Providers/Filament/
    AdminPanelProvider.php    ← panel /admin (Filament) — satu-satunya panel
  Filament/
    Resources/ Pages/ Widgets/  ← LAPISAN 3: admin (Module, Quiz, dll)
  Http/Controllers/
    Public/                   ← LAPISAN 1: frontend publik (katalog UMKM)
    Portal/                   ← LAPISAN 2: portal warga LMS (belajar, kuis)
  Livewire/                   ← komponen interaktif portal (QuizPlayer, dll)
  Models/                     ← semua Eloquent model
  Services/                   ← business logic (jangan di Controller)
  Policies/                   ← authorization per model

resources/views/
  public/                     ← Blade frontend publik
  portal/                     ← Blade portal warga

database/
  migrations/                 ← semua migration, lihat docs/DATABASE.md
  seeders/                    ← data dummy 3 nagari

docs/
  PRD.md                      ← spesifikasi lengkap sistem
  DATABASE.md                 ← skema tabel + relasi
  UI-GUIDE.md                 ← panduan warna & komponen
```

---

## Commands yang sering dipakai

```bash
php artisan serve                   # jalankan server development
npm run dev                         # compile assets
php artisan migrate                 # jalankan migration
php artisan migrate:fresh --seed    # reset + seed data dummy
php artisan test                    # jalankan semua test (Pest)
./vendor/bin/pint                   # format kode PHP
php artisan queue:work              # jalankan queue worker
```

---

## Konvensi penamaan

### PHP / Laravel
```
Model       : PascalCase singular     → Nagari, ModulePage, UmkmProduct
Controller  : PascalCase + Controller → NagariController
Service     : PascalCase + Service    → LmsProgressService
Policy      : PascalCase + Policy     → UmkmProductPolicy
Migration   : snake_case deskriptif  → create_umkm_products_table
Seeder      : PascalCase + Seeder     → NagariSeeder
```

### Database
```
Tabel       : snake_case plural       → umkm_products, sdgs_activities
Kolom       : snake_case              → nagari_id, created_at
Foreign key : {model}_id             → nagari_id, user_id
```

### Filament
```
Resource    : PascalCase + Resource   → UmkmProductResource
Widget      : PascalCase + Widget     → SdgsChartWidget
```

### Blade
```
Komponen    : kebab-case              → <x-nagari-card>
File view   : kebab-case              → nagari-card.blade.php
```

---

## Pola kode yang benar

### Service layer — business logic di sini, bukan di Controller

```php
// ✅ BENAR
class LmsProgressService
{
    public function completePage(User $user, ModulePage $page): UserModuleProgress
    {
        // semua logic di sini
    }
}

// ✅ Controller hanya delegasi
public function complete(Module $module, LmsProgressService $service)
{
    $progress = $service->completeModule(auth()->user(), $module);
    return redirect()->back()->with('success', 'Modul selesai!');
}
```

### Multi-tenancy — wajib scope ke nagari

```php
// ✅ BENAR
$products = UmkmProduct::whereHas('umkmProfile', fn($q) =>
    $q->where('nagari_id', auth()->user()->nagari_id)
)->get();

// ❌ SALAH — expose data nagari lain
$products = UmkmProduct::all();
```

### Migration — template standar

```php
Schema::create('nama_tabel', function (Blueprint $table) {
    $table->id();
    $table->foreignId('nagari_id')->constrained()->cascadeOnDelete(); // WAJIB
    $table->timestamps();
    $table->softDeletes(); // untuk data penting
    $table->index('nagari_id');  // WAJIB
});
```

---

## Aturan wajib — jangan dilanggar

1. Jangan buat file baru tanpa cek struktur yang sudah ada
2. Semua business logic masuk `app/Services/` — Controller hanya delegasi
3. Setiap tabel baru wajib punya `nagari_id` kecuali tabel global
4. Jangan hardcode string — pakai konstanta atau config
5. Setiap fitur baru wajib ada Policy — jangan skip authorization
6. Commit kecil dan sering — satu commit = satu perubahan logis
7. Update `PROGRESS.md` di akhir setiap sesi
8. Jangan install package baru tanpa catat di `DECISIONS.md`
9. Jangan gunakan `dd()`, `var_dump()`, `dump()` di kode yang akan di-commit
10. Jangan hardcode kredensial — pakai `.env`
11. Jangan merge ke `main` langsung — selalu via branch

---

## Git workflow

```bash
# Branch naming
feature/lms-quiz-engine
fix/umkm-approval-bug
chore/add-nagari-seeder

# Commit format
feat: tambah sistem poin leaderboard LMS
fix: perbaiki scope nagari di UmkmResource
docs: update PROGRESS.md sesi akhir
```

---

## Referensi dokumen

| File | Kapan dibaca |
|---|---|
| `TASKS.md` | Awal setiap sesi — cek task aktif |
| `PROGRESS.md` | Awal & akhir setiap sesi |
| `DECISIONS.md` | Sebelum keputusan arsitektur baru |
| `CONVENTIONS.md` | Saat ragu soal naming atau pola kode |
| `docs/PRD.md` | Cek spesifikasi fitur |
| `docs/DATABASE.md` | Sebelum buat migration baru |
| `docs/UI-GUIDE.md` | Saat kerjakan UI portal warga |

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
