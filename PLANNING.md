# PLANNING.md — Smart Learning Center Basamo NCH

> Dokumen arsitektur dan perencanaan teknis tingkat tinggi.
> Baca ini saat membuat keputusan struktural atau arsitektur baru.
> Untuk spesifikasi fitur lengkap → docs/PRD.md

---

## Gambaran sistem — 3 lapisan, satu Laravel, satu MySQL

```
basamo-nch/
│
├── LAPISAN 1 — Frontend Publik (Blade + Tailwind, controller standar)
│   ├── Tanpa login · SEO · ringan · cepat
│   ├── Route: / (home + katalog UMKM), /umkm/{produk}, /nagari/{slug}, /berita
│   └── app/Http/Controllers/Public/
│
├── LAPISAN 2 — Portal Warga LMS (Blade + Livewire/Alpine)
│   ├── Auth Laravel standar · middleware role ∈ (warga, umkm_owner)
│   ├── UX ramah HP · scalable untuk ratusan ribu user
│   ├── Fitur: belajar modul, kuis, progress, leaderboard, input produk UMKM
│   └── app/Http/Controllers/Portal/ + app/Livewire/
│
├── LAPISAN 3 — Panel Admin (Filament v5, /admin)
│   ├── Auth Filament · canAccessPanel cek role ∈ (super_admin, nagari_admin)
│   ├── Tenant: Nagari::class (multi-tenancy otomatis)
│   ├── Fitur: Dashboard, kelola modul/kuis, SDGs, verifikasi UMKM, IoT, Laporan
│   └── app/Filament/ + app/Providers/Filament/AdminPanelProvider.php
│
└── Database MySQL — multi-tenant via nagari_id pada semua tabel
```

---

## Arsitektur aplikasi

### Layer pattern

```
HTTP Request
    ↓
Filament Resource / Controller
    ↓
Service Layer (app/Services/)     ← semua business logic di sini
    ↓
Eloquent Model (app/Models/)
    ↓
MySQL Database
```

### Direktori proyek (target akhir)

```
app/
├── Filament/                     ← LAPISAN 3: panel admin saja
│   ├── Resources/                ← NagariResource, ModuleResource, QuizResource, dll
│   ├── Pages/                    ← halaman custom dashboard admin
│   └── Widgets/                  ← SdgsChartWidget, LmsStatsWidget, dll
├── Providers/Filament/
│   └── AdminPanelProvider.php    ← konfigurasi panel /admin (satu-satunya panel)
├── Http/Controllers/
│   ├── Public/                   ← LAPISAN 1: frontend publik
│   │   ├── HomeController.php     ← halaman utama + katalog UMKM
│   │   ├── UmkmCatalogController.php
│   │   └── NagariProfileController.php
│   └── Portal/                   ← LAPISAN 2: portal warga LMS
│       ├── DashboardController.php
│       ├── ModuleController.php   ← daftar & detail modul
│       └── UmkmProductController.php  ← input produk (umkm_owner)
├── Livewire/                     ← komponen interaktif portal warga
│   ├── QuizPlayer.php             ← kerjakan kuis
│   └── Leaderboard.php
├── Models/
│   ├── Nagari.php · User.php (HasRoles, canAccessPanel)
│   ├── Module.php · ModulePage.php
│   ├── Quiz.php · QuizQuestion.php · QuizOption.php
│   ├── QuizAttempt.php · QuizAnswer.php
│   ├── UserModuleProgress.php · Discussion.php
│   └── (nanti) SdgsActivity, UmkmProfile, UmkmProduct, IotSensor, dll
├── Services/
│   ├── LmsProgressService.php    ← progress belajar
│   ├── LmsPointService.php       ← kalkulasi poin & leaderboard
│   └── (nanti) SdgsScoreService, UmkmService, IotSimulatorService
└── Policies/
    ├── ModulePolicy.php · QuizPolicy.php
    └── (nanti) UmkmProductPolicy, SdgsActivityPolicy

database/
├── migrations/                   ← lihat DATABASE.md
└── seeders/
    ├── DatabaseSeeder.php
    ├── NagariSeeder.php          ← nagari dummy
    ├── UserSeeder.php            ← admin, warga per nagari
    └── ModuleSeeder.php          ← modul + materi + kuis dummy
    (seeder SDGs/UMKM/IoT menyusul saat fiturnya dikerjakan)

resources/views/
├── public/                       ← LAPISAN 1: Blade frontend publik
│   ├── home.blade.php            ← halaman utama + katalog UMKM
│   ├── umkm/catalog.blade.php
│   └── nagari/profile.blade.php
├── portal/                       ← LAPISAN 2: Blade portal warga
│   ├── dashboard.blade.php
│   ├── modules/index.blade.php · show.blade.php
│   └── layouts/portal.blade.php
└── components/                   ← Blade components reusable

routes/
└── web.php                       ← route publik + portal (admin di-handle Filament)
``````

---

## Multi-tenancy — cara kerjanya

### Super Admin
Melihat semua data lintas nagari. Tidak di-scope.

### Admin Nagari
Di-scope otomatis ke `nagari_id` miliknya via Filament Tenancy:

```php
// Di AdminPanelProvider
->tenant(Nagari::class, ownershipRelationship: 'nagari')

// Model Nagari implements HasTenants
class Nagari extends Model implements HasTenants {
    public function getTenants(Panel $panel): Collection {
        return Nagari::all(); // super admin lihat semua
    }
}
```

### Warga & UMKM
Di-scope via middleware dan relationship ke `nagari_id` user yang login.

---

## Sistem poin LMS

| Aktivitas | Poin |
|---|---|
| Selesaikan satu halaman materi | 10 |
| Lulus kuis pilihan ganda | 20 |
| Lulus kuis essay (admin nilai) | 30 |
| Selesaikan satu modul penuh | +50 bonus |

Leaderboard per nagari — bukan lintas nagari.
Dikelola oleh `LmsPointService`.

---

## Threshold sensor IoT (simulasi)

| Parameter | Normal | Waspada | Bahaya |
|---|---|---|---|
| Suhu (°C) | ≤ 35 | 35–40 | > 40 |
| Kelembaban (%) | ≥ 20 | 10–20 | < 10 |
| Ketinggian air (cm) | ≤ 50 | 50–100 | > 100 |
| Kecepatan angin (km/h) | ≤ 40 | 40–70 | > 70 |

---

## Dependency yang sudah disepakati

Lihat `CLAUDE.md` bagian Stack. Jangan tambah dependency baru tanpa
dicatat di `DECISIONS.md` dan diupdate di `CLAUDE.md`.
