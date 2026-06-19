# DATABASE.md — Smart Learning Center Basamo NCH

> Referensi skema database lengkap. Baca sebelum membuat migration baru.
> Semua tabel wajib punya `nagari_id` kecuali tabel global yang ditandai 🌐

---

## Diagram relasi ringkas

```
nagaris
  └─< users (nagari_id)
  └─< modules (nagari_id nullable = global 🌐)
  └─< sdgs_activities (nagari_id)
  └─< umkm_profiles (nagari_id)
  └─< iot_sensors (nagari_id)
  └─< news_feeds (nagari_id nullable = global 🌐)

users
  └─< user_module_progress (user_id)
  └─< quiz_attempts (user_id)
  └─< discussions (user_id)
  └─< umkm_profiles (user_id) ← pemilik UMKM
  └─< sdgs_activities (created_by)

modules
  └─< module_pages (module_id)
  └─< quizzes (module_id)
  └─< discussions (module_id)
  └─< user_module_progress (module_id)

quizzes
  └─< quiz_questions (quiz_id)
      └─< quiz_options (question_id)
  └─< quiz_attempts (quiz_id)
      └─< quiz_answers (attempt_id, question_id)

umkm_profiles
  └─< umkm_products (umkm_profile_id)
      └─< umkm_product_photos (product_id)

sdgs_activities
  └─< sdgs_documents (activity_id)

iot_sensors
  └─< iot_readings (sensor_id)
```

---

## Tabel detail

### `nagaris` — Master data nagari
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
nama                VARCHAR(255) NOT NULL
kode                VARCHAR(50)  UNIQUE NOT NULL
provinsi            VARCHAR(100)
kabupaten           VARCHAR(100)
kecamatan           VARCHAR(100)
koordinat_lat       DECIMAL(10,8) NULLABLE
koordinat_lng       DECIMAL(11,8) NULLABLE
kontak              VARCHAR(20) NULLABLE   -- nomor WA admin
status              ENUM('active','inactive') DEFAULT 'active'
created_at, updated_at
```

### `users` 🌐 — Semua pengguna sistem
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
nagari_id           BIGINT UNSIGNED FK → nagaris (NULLABLE untuk super_admin)
name                VARCHAR(255) NOT NULL
username            VARCHAR(255) UNIQUE NULLABLE  -- admin: username; warga/umkm: NIK 16 digit (login)
email               VARCHAR(255) UNIQUE NULLABLE  -- opsional untuk warga (banyak NULL diizinkan)
phone               VARCHAR(20) NULLABLE          -- No. WhatsApp/HP warga
email_verified_at   TIMESTAMP NULLABLE
password            VARCHAR(255) NOT NULL
must_change_password BOOLEAN DEFAULT FALSE        -- paksa ganti sandi di login pertama (OTP)
initial_otp         VARCHAR(12) NULLABLE          -- OTP awal (sementara), dikosongkan setelah ganti sandi
role                ENUM('super_admin','nagari_admin','warga','umkm_owner')
avatar              VARCHAR(255) NULLABLE
total_xp            INT UNSIGNED DEFAULT 0   -- XP LMS leaderboard (idempotent via xp_logs)
status              ENUM('active','inactive') DEFAULT 'active'
remember_token      VARCHAR(100) NULLABLE
created_at, updated_at, deleted_at

INDEX(nagari_id), INDEX(role)
```
> **Provisioning warga:** akun dibuat Admin Nagari (tanpa self-register). Warga login dengan
> **NIK (username) + OTP** (sandi awal). OTP `initial_otp` ditampilkan ke admin untuk disampaikan;
> login pertama memaksa ganti sandi (`must_change_password`). Login portal terima NIK atau email
> (+ rate-limit). `password`/`initial_otp` tak pernah dilog/diserialisasi.

### `modules` — Modul LMS
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
nagari_id           BIGINT UNSIGNED FK → nagaris NULLABLE  -- NULL = modul global
title               VARCHAR(255) NOT NULL
slug                VARCHAR(255) UNIQUE NOT NULL
description         TEXT NULLABLE
sort_order          SMALLINT UNSIGNED DEFAULT 0   -- (dulu `order`, kata kunci SQL)
estimated_minutes   SMALLINT UNSIGNED NULLABLE    -- estimasi durasi belajar (menit)
prerequisite_module_id  BIGINT UNSIGNED FK → modules NULLABLE
status              ENUM('draft','published') DEFAULT 'draft'
created_by          BIGINT UNSIGNED FK → users NULLABLE (nullOnDelete)
created_at, updated_at, deleted_at

INDEX(nagari_id), INDEX(status), INDEX(sort_order)
-- Cover modul: via Spatie Media Library (koleksi `cover`, singleFile, konversi
--   `card` webp 800×450). Fallback global: public/images/default-module-cover.svg.
```

### `module_pages` — Halaman materi per modul
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
module_id           BIGINT UNSIGNED FK → modules CASCADE DELETE
title               VARCHAR(255) NOT NULL
type                ENUM('text','pdf','video') NOT NULL
content             LONGTEXT NULLABLE        -- konten RichEditor Filament (HTML)
video_url           VARCHAR(500) NULLABLE    -- YouTube / Google Drive embed URL
file_path           VARCHAR(500) NULLABLE    -- path PDF
sort_order          SMALLINT UNSIGNED DEFAULT 0   -- (dulu `order`)
created_at, updated_at

INDEX(module_id), INDEX(sort_order)
```

### `user_module_progress` — Progress belajar warga
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
user_id             BIGINT UNSIGNED FK → users CASCADE DELETE
module_id           BIGINT UNSIGNED FK → modules CASCADE DELETE
pages_completed     JSON NULLABLE            -- array page_id yang sudah selesai
status              ENUM('not_started','in_progress','completed')
completed_at        TIMESTAMP NULLABLE
created_at, updated_at

UNIQUE(user_id, module_id)
INDEX(user_id), INDEX(module_id), INDEX(status)
```

### `quizzes` — Kuis per modul
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
module_id           BIGINT UNSIGNED FK → modules CASCADE DELETE
-- tanpa kolom `title`: label diturunkan dari modul → "Kuis: {judul modul}"
--   (accessor Quiz::title). 1 modul = 1 kuis, judul redundan.
passing_score       TINYINT UNSIGNED DEFAULT 70   -- nilai minimum lulus (skala 0–100)
max_attempts        TINYINT UNSIGNED DEFAULT 3
created_at, updated_at

UNIQUE(module_id)
```

### `quiz_questions` — Soal kuis
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
quiz_id             BIGINT UNSIGNED FK → quizzes CASCADE DELETE
question            TEXT NOT NULL
sort_order          SMALLINT UNSIGNED DEFAULT 0   -- (dulu `order`)
created_at, updated_at

INDEX(quiz_id), INDEX(sort_order)
```
> Kuis **hanya pilihan ganda** (MVP). Tidak ada kolom `type`; setiap soal selalu punya `quiz_options`.
> **Jawaban benar boleh >1** (implisit: bila opsi `is_correct` >1 → soal pilihan jamak/checkbox).
> **Semua soal setara** (tanpa bobot poin). Nilai = (Σ fraksi soal ÷ jumlah soal) × 100, skala 0–100.
> Fraksi per soal (partial credit) = max(0, benar_terpilih/total_benar − salah_terpilih/total_salah).

### `quiz_options` — Pilihan jawaban (multiple choice)
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
question_id         BIGINT UNSIGNED FK → quiz_questions CASCADE DELETE
option_text         TEXT NOT NULL
is_correct          BOOLEAN DEFAULT FALSE   -- boleh >1 benar per soal
sort_order          TINYINT UNSIGNED DEFAULT 0   -- (dulu `order`)
created_at, updated_at

INDEX(question_id)
```

### `quiz_attempts` — Percobaan kuis warga
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
user_id             BIGINT UNSIGNED FK → users
quiz_id             BIGINT UNSIGNED FK → quizzes
score               TINYINT UNSIGNED NULLABLE    -- 0-100
status              ENUM('in_progress','passed','failed')   -- auto-grade, tanpa review
submitted_at        TIMESTAMP NULLABLE
created_at, updated_at

INDEX(user_id), INDEX(quiz_id), INDEX(status)
```

### `quiz_answers` — Jawaban per soal
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
attempt_id          BIGINT UNSIGNED FK → quiz_attempts CASCADE DELETE
question_id         BIGINT UNSIGNED FK → quiz_questions
selected_option_id  BIGINT UNSIGNED FK → quiz_options NULLABLE
is_correct          BOOLEAN NULLABLE     -- apakah opsi yang dipilih ini termasuk jawaban benar
created_at, updated_at

INDEX(attempt_id), INDEX(question_id)
-- Soal pilihan jamak: SATU baris per opsi yang dipilih (1 soal → beberapa baris).
```

### `discussions` — Forum diskusi per modul
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
module_id           BIGINT UNSIGNED FK → modules CASCADE DELETE
user_id             BIGINT UNSIGNED FK → users
parent_id           BIGINT UNSIGNED FK → discussions NULLABLE  -- untuk reply
body                TEXT NOT NULL
is_pinned           BOOLEAN DEFAULT FALSE
created_at, updated_at, deleted_at

INDEX(module_id), INDEX(user_id), INDEX(parent_id)
```

### `sdgs_activities` — Kegiatan per poin SDGs
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
nagari_id           BIGINT UNSIGNED FK → nagaris
sdgs_point          TINYINT UNSIGNED NOT NULL   -- 1-18
title               VARCHAR(255) NOT NULL
description         TEXT NULLABLE
pelaksana           VARCHAR(255) NULLABLE
tanggal_kegiatan    DATE NULLABLE
jumlah_peserta      SMALLINT UNSIGNED NULLABLE
created_by          BIGINT UNSIGNED FK → users
created_at, updated_at, deleted_at

INDEX(nagari_id), INDEX(sdgs_point)
```

### `sdgs_documents` — Dokumen bukti kegiatan SDGs
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
activity_id         BIGINT UNSIGNED FK → sdgs_activities CASCADE DELETE
file_name           VARCHAR(255) NOT NULL
file_path           VARCHAR(500) NOT NULL
file_type           VARCHAR(50)                 -- pdf, jpg, png
file_size           INT UNSIGNED NULLABLE       -- bytes
created_at, updated_at

INDEX(activity_id)
```

### `umkm_profiles` — Profil usaha UMKM
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
nagari_id           BIGINT UNSIGNED FK → nagaris
user_id             BIGINT UNSIGNED FK → users   -- pemilik UMKM
nama_usaha          VARCHAR(255) NOT NULL
slug                VARCHAR(255) UNIQUE NOT NULL
kategori            VARCHAR(100) NOT NULL
deskripsi           TEXT NULLABLE
alamat              TEXT NULLABLE
whatsapp            VARCHAR(20) NOT NULL
status              ENUM('active','inactive') DEFAULT 'active'
created_at, updated_at, deleted_at

INDEX(nagari_id), INDEX(kategori), INDEX(status)
```

### `umkm_products` — Produk UMKM
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
umkm_profile_id     BIGINT UNSIGNED FK → umkm_profiles CASCADE DELETE
nama_produk         VARCHAR(255) NOT NULL
slug                VARCHAR(255) UNIQUE NOT NULL
deskripsi           TEXT NULLABLE
harga               DECIMAL(12,2) NULLABLE
status              ENUM('pending','approved','rejected') DEFAULT 'pending'
rejection_reason    TEXT NULLABLE
approved_by         BIGINT UNSIGNED FK → users NULLABLE
approved_at         TIMESTAMP NULLABLE
view_count          INT UNSIGNED DEFAULT 0
created_at, updated_at, deleted_at

INDEX(umkm_profile_id), INDEX(status)
```

### `umkm_product_photos` — Foto produk
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
product_id          BIGINT UNSIGNED FK → umkm_products CASCADE DELETE
file_path           VARCHAR(500) NOT NULL
order               TINYINT UNSIGNED DEFAULT 0
created_at, updated_at

INDEX(product_id), INDEX(order)
```

### `iot_sensors` — Master sensor IoT per nagari
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
nagari_id           BIGINT UNSIGNED FK → nagaris
nama                VARCHAR(255) NOT NULL
tipe                VARCHAR(100) NOT NULL       -- suhu, kelembaban, air, angin
lokasi              VARCHAR(255) NULLABLE
status              ENUM('active','inactive','error') DEFAULT 'active'
created_at, updated_at

INDEX(nagari_id), INDEX(tipe)
```

### `iot_readings` — Data pembacaan sensor
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
sensor_id           BIGINT UNSIGNED FK → iot_sensors CASCADE DELETE
nilai               DECIMAL(8,2) NOT NULL
satuan              VARCHAR(20) NOT NULL        -- °C, %, cm, km/h
status              ENUM('normal','waspada','bahaya') NOT NULL
recorded_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
created_at

INDEX(sensor_id), INDEX(status), INDEX(recorded_at)
-- Data lama otomatis di-prune via scheduled command (simpan 30 hari)
```

### `notifications` 🌐 — Notifikasi in-app
```sql
-- Menggunakan tabel bawaan Laravel Notifications
id                  CHAR(36) PK              -- UUID
type                VARCHAR(255) NOT NULL
notifiable_type     VARCHAR(255) NOT NULL
notifiable_id       BIGINT UNSIGNED NOT NULL
data                JSON NOT NULL
read_at             TIMESTAMP NULLABLE
created_at, updated_at

INDEX(notifiable_type, notifiable_id)
```

### `news_feeds` — Berita & aktivitas nagari
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
nagari_id           BIGINT UNSIGNED FK → nagaris NULLABLE  -- NULL = berita global
title               VARCHAR(255) NOT NULL
body                TEXT NOT NULL
type                ENUM('berita','aktivitas','pengumuman') DEFAULT 'berita'
created_by          BIGINT UNSIGNED FK → users
created_at, updated_at, deleted_at

INDEX(nagari_id), INDEX(type)
```

---

## Catatan penting

- **Soft delete** diaktifkan pada: `users`, `modules`, `discussions`, `sdgs_activities`, `umkm_profiles`, `umkm_products`, `news_feeds`
- **IoT readings** di-prune otomatis setelah 30 hari via scheduled command
- **Slug** di-generate otomatis via `spatie/laravel-sluggable` pada: `modules`, `umkm_profiles`, `umkm_products`
- **Media file** (foto produk, dokumen SDGs) dikelola via `spatie/laravel-medialibrary` — tidak disimpan manual di tabel
