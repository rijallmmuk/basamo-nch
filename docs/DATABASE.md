# DATABASE.md — Basamo NCH

> Referensi skema database **aktual** (cerminan migrasi di `database/migrations`).
> Baca sebelum membuat/mengubah migrasi. Multi-tenancy: tabel milik desa punya
> `desa_id` (atau diturunkan via relasi). Migrasi dikelompokkan per-domain.

---

## Konvensi penamaan

- **Kolom**: bahasa Indonesia, kecuali yang standar Inggris → `id`, `*_id` (FK),
  `*_at` (timestamp), `slug`, `status`, `is_*` (boolean), `created_by`/`approved_by`,
  dan kolom domain auth pada `users` (`name`, `username`, `email`, `password`, `role`).
- **Nama tabel & model**: tetap Inggris (idiom Laravel), mis. `modules` → `Module`.
- **Soft delete** pada data penting; **slug** via `spatie/laravel-sluggable`;
  **media** (cover modul, foto produk, logo desa) via `spatie/laravel-medialibrary`
  (tidak ada tabel foto manual).

---

## Diagram relasi ringkas

```
ref_wilayah (kode) ─< desas.wilayah_kode
ref_wilayah ─1:1─ wilayah_boundaries (kode, geometri peta)

desas
  ├─ jenis_desa_id      → jenis_desa
  ├─ jenis_sub_unit_id  → jenis_sub_unit
  └─< desa_units (desa_id) ─< users.desa_unit_id

users (desa_id, desa_unit_id)
  └─< user_module_progress · quiz_attempts · discussions · xp_logs
  └─1:1 umkm_profiles (pemilik)

modules (desa_id nullable = global)
  ├─< module_pages
  ├─1:1 quizzes ─< quiz_questions ─< quiz_options
  │                 quizzes ─< quiz_attempts ─< quiz_answers
  ├─< discussions (threaded via parent_id)
  └─< user_module_progress

umkm_categories ─< umkm_profiles ─< umkm_products
```

---

## 1. Referensi wilayah — `create_wilayah_reference_tables`

### `ref_wilayah` 🌐 — referensi wilayah administratif (Kepmendagri)
```
kode         VARCHAR(13) PK          -- dotted, mis. 13.06.01.2001
nama         VARCHAR(100)
level        TINYINT UNSIGNED        -- 1=prov, 2=kab/kota, 3=kec, 4=desa/kel
parent_kode  VARCHAR(13) NULL
ibukota      VARCHAR(100) NULL
lat, lng     DOUBLE NULL             -- prov & kab/kota saja
elv          FLOAT NULL              -- elevasi (m)
tz           TINYINT NULL            -- zona waktu (jam)
luas         DOUBLE NULL             -- km²
penduduk     BIGINT UNSIGNED NULL
INDEX(level), INDEX(parent_kode)
```

### `wilayah_boundaries` 🌐 — geometri batas (sumber peta)
```
kode             VARCHAR(13) PK      -- join ke ref_wilayah.kode
level            TINYINT UNSIGNED
parent_kode      VARCHAR(13) NULL
nama             VARCHAR(150)
lat, lng         DOUBLE NULL         -- titik tengah (marker)
geom             GEOMETRY            -- MULTIPOLYGON penuh (ST_Contains)
geom_simplified  GEOMETRY NULL       -- versi ringan (render)
INDEX(level), INDEX(parent_kode), SPATIAL(geom)  -- spatial hanya MySQL/MariaDB
```
> Dipisah dari `ref_wilayah` agar tabel referensi ringan. MariaDB tak punya
> `ST_Simplify` → `geom_simplified` dihasilkan saat impor (Douglas–Peucker).

---

## 2. Jenis penyebutan — `create_jenis_wilayah_tables`

### `jenis_desa` 🌐 / `jenis_sub_unit` 🌐 — di-seed di migrasi
```
id      BIGINT UNSIGNED PK
nama    VARCHAR(50) UNIQUE   -- jenis_desa: Desa/Kelurahan/Nagari/… ; sub_unit: Jorong/Dusun/…
urutan  SMALLINT UNSIGNED
aktif   BOOLEAN DEFAULT TRUE
```

---

## 3. Desa & sub-unit — `create_desa_tables`

### `desas` — desa/nagari tenant
```
id                 BIGINT UNSIGNED PK
nama               VARCHAR(255)
wilayah_kode       VARCHAR(13) NULL FK → ref_wilayah.kode (nullOnDelete)
jenis_desa_id      BIGINT UNSIGNED FK → jenis_desa (restrictOnDelete)
provinsi           VARCHAR(100) NULL   -- denormalized utk display cepat
kabupaten          VARCHAR(100) NULL
kecamatan          VARCHAR(100) NULL
jenis_sub_unit_id  BIGINT UNSIGNED NULL FK → jenis_sub_unit (nullOnDelete)
koordinat_lat      DECIMAL(10,8) NULL
koordinat_lng      DECIMAL(11,8) NULL
kontak             VARCHAR(20) NULL
status             VARCHAR (ActiveStatus) DEFAULT 'active'
+ timestamps, softDeletes
INDEX(wilayah_kode)
```

### `desa_units` — sub-unit dalam desa (Jorong/Dusun/Korong/…)
```
id       BIGINT UNSIGNED PK
desa_id  BIGINT UNSIGNED FK → desas (cascadeOnDelete)
nama     VARCHAR(255)
+ timestamps, softDeletes
INDEX(desa_id), UNIQUE(desa_id, nama)
```
> Model `DesaUnit`. Sebutannya (Jorong/Dusun/…) diturunkan dari `desas.jenis_sub_unit_id`.

---

## 4. Pengguna — `create_users_table`

### `users` 🌐
```
id                     BIGINT UNSIGNED PK
desa_id                BIGINT UNSIGNED NULL FK → desas (nullOnDelete; null = super_admin)
desa_unit_id           BIGINT UNSIGNED NULL FK → desa_units (nullOnDelete; alamat warga)
name                   VARCHAR(255)
username               VARCHAR(255) UNIQUE NULL   -- admin: username; warga: NIK 16 digit (login)
email                  VARCHAR(255) UNIQUE NULL
phone                  VARCHAR(20) NULL
email_verified_at      TIMESTAMP NULL
password               VARCHAR(255)
must_change_password   BOOLEAN DEFAULT FALSE      -- paksa ganti sandi saat login pertama (OTP)
initial_otp            VARCHAR(12) NULL           -- OTP awal; dihapus setelah sandi diganti
role                   VARCHAR(20) NULL           -- super_admin | desa_admin | warga
umkm_access_granted_at TIMESTAMP NULL             -- kapabilitas UMKM (bukan role)
total_xp               INT UNSIGNED DEFAULT 0
status                 VARCHAR DEFAULT 'active'
+ rememberToken, timestamps, softDeletes
INDEX(role), INDEX(desa_id, role, status, total_xp)  -- komposit leaderboard
```
> Warga di-provisioning Admin Desa (tanpa self-register), login **NIK + OTP**;
> `password`/`initial_otp` tak pernah dilog/diserialisasi. Akses UMKM = warga +
> `umkm_access_granted_at` terisi (bukan role terpisah).

---

## 5. LMS modul — `create_lms_module_tables`

### `modules`
```
id                   BIGINT UNSIGNED PK
desa_id              BIGINT UNSIGNED NULL FK → desas (null = modul global 🌐)
judul                VARCHAR(255)
slug                 VARCHAR(255) UNIQUE
deskripsi            TEXT NULL
urutan               SMALLINT UNSIGNED DEFAULT 0
estimasi_menit       SMALLINT UNSIGNED NULL
prasyarat_module_id  BIGINT UNSIGNED NULL FK → modules (nullOnDelete)
status               VARCHAR (ModuleStatus) DEFAULT 'draft'   -- draft | published
created_by           BIGINT UNSIGNED NULL FK → users (nullOnDelete)
+ timestamps, softDeletes
INDEX(desa_id), INDEX(status), INDEX(urutan)
```
> Cover via Media Library (koleksi `cover`, konversi `card` webp 800×450).

### `module_pages`
```
id         BIGINT UNSIGNED PK
module_id  BIGINT UNSIGNED FK → modules (cascadeOnDelete)
judul      VARCHAR(255)
tipe       VARCHAR (ModulePageType)   -- text | video | pdf
konten     LONGTEXT NULL              -- HTML RichEditor (tipe text)
url_video  VARCHAR(500) NULL          -- YouTube/Drive embed (tipe video)
path_file  VARCHAR(500) NULL          -- path PDF (tipe pdf)
urutan     SMALLINT UNSIGNED DEFAULT 0
INDEX(module_id), INDEX(urutan)
```

### `user_module_progress`
```
id               BIGINT UNSIGNED PK
user_id          BIGINT UNSIGNED FK → users (cascadeOnDelete)
module_id        BIGINT UNSIGNED FK → modules (cascadeOnDelete)
halaman_selesai  JSON NULL                  -- array page_id selesai
status           VARCHAR (ModuleProgressStatus) DEFAULT 'not_started'
completed_at     TIMESTAMP NULL
UNIQUE(user_id, module_id), INDEX(module_id), INDEX(status)
```

### `discussions`
```
id         BIGINT UNSIGNED PK
module_id  BIGINT UNSIGNED FK → modules (cascadeOnDelete)
user_id    BIGINT UNSIGNED FK → users (cascadeOnDelete)
parent_id  BIGINT UNSIGNED NULL FK → discussions (cascadeOnDelete; reply)
isi        TEXT
is_pinned  BOOLEAN DEFAULT FALSE
+ timestamps, softDeletes
INDEX(user_id), INDEX(parent_id), INDEX(module_id, parent_id)
```

### `xp_logs` — ledger XP gamifikasi
```
id         BIGINT UNSIGNED PK
user_id    BIGINT UNSIGNED FK → users (cascadeOnDelete)
desa_id    BIGINT UNSIGNED NULL FK → desas (nullOnDelete)
sumber     VARCHAR(20)              -- module | quiz | discussion
sumber_id  BIGINT UNSIGNED         -- id sumber XP
jumlah     SMALLINT UNSIGNED
UNIQUE(user_id, sumber, sumber_id)  -- XP per pencapaian sekali (idempotent)
INDEX(desa_id)
```

---

## 6. LMS kuis — `create_lms_quiz_tables`

### `quizzes`
```
id              BIGINT UNSIGNED PK
module_id       BIGINT UNSIGNED UNIQUE FK → modules (cascadeOnDelete)  -- 1 modul = 1 kuis
nilai_lulus     TINYINT UNSIGNED DEFAULT 70   -- skala 0–100
maks_percobaan  TINYINT UNSIGNED DEFAULT 3    -- 0 = tak terbatas
```
> Tanpa kolom judul: label diturunkan → "Kuis: {judul modul}" (accessor `Quiz::title`).

### `quiz_questions`
```
id          BIGINT UNSIGNED PK
quiz_id     BIGINT UNSIGNED FK → quizzes (cascadeOnDelete)
pertanyaan  TEXT
urutan      SMALLINT UNSIGNED DEFAULT 0
INDEX(quiz_id), INDEX(urutan)
```

### `quiz_options`
```
id           BIGINT UNSIGNED PK
question_id  BIGINT UNSIGNED FK → quiz_questions (cascadeOnDelete)
teks_opsi    TEXT
is_correct   BOOLEAN DEFAULT FALSE   -- boleh >1 benar → soal pilihan jamak
urutan       TINYINT UNSIGNED DEFAULT 0
INDEX(question_id)
```
> Pilihan ganda; nilai = (Σ fraksi soal ÷ jumlah soal) × 100. Fraksi (partial credit)
> = max(0, benar_terpilih/total_benar − salah_terpilih/total_salah).

### `quiz_attempts`
```
id            BIGINT UNSIGNED PK
user_id       BIGINT UNSIGNED FK → users (cascadeOnDelete)
quiz_id       BIGINT UNSIGNED FK → quizzes (cascadeOnDelete)
nilai         TINYINT UNSIGNED NULL   -- 0–100
status        VARCHAR (QuizAttemptStatus)  -- passed | failed (auto-grade sinkron)
submitted_at  TIMESTAMP NULL
INDEX(quiz_id), INDEX(status), INDEX(user_id, quiz_id, status)
```

### `quiz_answers`
```
id                  BIGINT UNSIGNED PK
attempt_id          BIGINT UNSIGNED FK → quiz_attempts (cascadeOnDelete)
question_id         BIGINT UNSIGNED FK → quiz_questions (cascadeOnDelete)
selected_option_id  BIGINT UNSIGNED NULL FK → quiz_options (nullOnDelete)
is_correct          BOOLEAN NULL
INDEX(attempt_id), INDEX(question_id)
```
> Soal pilihan jamak: SATU baris per opsi yang dipilih (1 soal → beberapa baris).

---

## 7. UMKM — `create_umkm_tables`

### `umkm_categories` 🌐 — di-seed di migrasi
```
id      BIGINT UNSIGNED PK
nama    VARCHAR(100)
slug    VARCHAR(100) UNIQUE
icon    VARCHAR(60) NULL    -- nama Heroicon
urutan  SMALLINT UNSIGNED DEFAULT 0
```

### `umkm_profiles` — profil usaha (1 warga = 1 lapak)
```
id                BIGINT UNSIGNED PK
desa_id           BIGINT UNSIGNED FK → desas (cascadeOnDelete)
user_id           BIGINT UNSIGNED UNIQUE FK → users (cascadeOnDelete)
umkm_category_id  BIGINT UNSIGNED NULL FK → umkm_categories (nullOnDelete)
nama_usaha        VARCHAR(255)
slug              VARCHAR(255) UNIQUE
deskripsi         TEXT NULL
alamat            TEXT NULL
whatsapp          VARCHAR(20)
status            VARCHAR (ActiveStatus) DEFAULT 'active'
+ timestamps, softDeletes
INDEX(desa_id), INDEX(status)
```

### `umkm_products`
```
id                BIGINT UNSIGNED PK
umkm_profile_id   BIGINT UNSIGNED FK → umkm_profiles (cascadeOnDelete)
nama_produk       VARCHAR(255)
slug              VARCHAR(255) UNIQUE
deskripsi         TEXT NULL
harga             BIGINT UNSIGNED NULL   -- rupiah integer
status            VARCHAR (UmkmProductStatus) DEFAULT 'pending'  -- pending|approved|rejected
alasan_penolakan  TEXT NULL
approved_by       BIGINT UNSIGNED NULL FK → users (nullOnDelete)
approved_at       TIMESTAMP NULL
jumlah_dilihat    INT UNSIGNED DEFAULT 0
+ timestamps, softDeletes
INDEX(umkm_profile_id), INDEX(status, approved_at)
FULLTEXT(nama_produk, deskripsi)  -- hanya MySQL/MariaDB
```
> Foto via Media Library (koleksi `photos`, maks 5).

---

## Tabel framework/paket (tidak dimodifikasi)

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`,
`failed_jobs`, `migrations` (Laravel); `media` (Spatie Media Library);
`permissions`, `roles`, `model_has_permissions`, `model_has_roles`,
`role_has_permissions` (Spatie Permission / Shield); `activity_log`
(Spatie Activitylog); `notifications` (Laravel Notifications, in-app portal).

---

## Seeding

- **Migrasi**: enum referensi kecil & fixed → `jenis_desa`, `jenis_sub_unit`, `umkm_categories`.
- **`CoreSeeder`** (esensial produksi, idempotent): role RBAC + super admin +
  `WilayahSumbarSeeder` (ref_wilayah) + `WilayahBoundarySeeder` (geometri).
- **`DemoSeeder`** (dev): memanggil CoreSeeder, lalu 2 desa + sub-unit + warga
  (login NIK) + modul/kuis + progres/XP + UMKM. Jalankan: `migrate:fresh --seed`.

---

## Roadmap (belum dibangun)

Pilar **SDGs** & **IoT** dan **berita/news** ada di visi produk (lihat `PRD.md`)
namun **belum** punya tabel/migrasi. Saat dibangun, ikuti konvensi di atas
(tenant `desa_id`, penamaan Indonesia, soft delete bila perlu).
