# UI-GUIDE.md — Smart Learning Center Basamo NCH

> Panduan visual untuk konsistensi tampilan di seluruh platform.
> Berlaku untuk Admin Panel (Filament theme) dan Portal Warga (custom Tailwind).

---

## Warna sistem

### Panel Admin (Filament theme color)
```php
// AdminPanelProvider.php (panel admin Filament). Portal warga pakai Blade custom.
->colors([
    'primary' => Color::Indigo,
])
```

### Portal Warga (Tailwind CSS variables)

| Peran | Nama | Hex | Tailwind class |
|---|---|---|---|
| Aksi utama | Primary | `#4F46E5` | `indigo-600` |
| Sukses / selesai | Success | `#16A34A` | `green-600` |
| Peringatan | Warning | `#D97706` | `amber-600` |
| Bahaya / error | Danger | `#DC2626` | `red-600` |
| Informasi | Info | `#2563EB` | `blue-600` |
| Teks utama | Gray 900 | `#111827` | `gray-900` |
| Teks sekunder | Gray 500 | `#6B7280` | `gray-500` |
| Border | Gray 200 | `#E5E7EB` | `gray-200` |
| Background halaman | Gray 50 | `#F9FAFB` | `gray-50` |
| Background card | White | `#FFFFFF` | `white` |

### Warna SDGs (18 poin — sesuai standar UN)
```
SDGs 1  : #E5243B    SDGs 10 : #DD1367
SDGs 2  : #DDA63A    SDGs 11 : #FD9D24
SDGs 3  : #4C9F38    SDGs 12 : #BF8B2E
SDGs 4  : #C5192D    SDGs 13 : #3F7E44
SDGs 5  : #FF3A21    SDGs 14 : #0A97D9
SDGs 6  : #26BDE2    SDGs 15 : #56C02B
SDGs 7  : #FCC30B    SDGs 16 : #00689D
SDGs 8  : #A21942    SDGs 17 : #19486A
SDGs 9  : #FD6925
```

---

## Tipografi

```css
/* Font stack — gunakan sistem font, tidak perlu Google Fonts */
font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;

/* Scale */
Heading 1  : text-2xl font-bold     (24px)
Heading 2  : text-xl font-semibold  (20px)
Heading 3  : text-lg font-medium    (18px)
Body       : text-sm                (14px)
Caption    : text-xs text-gray-500  (12px)
```

---

## Komponen portal warga

### Kartu modul

```html
<!-- Status: available -->
<div class="rounded-xl bg-white border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
  <img class="w-full h-32 object-cover rounded-lg mb-3" src="...">
  <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">Tersedia</span>
  <h3 class="mt-2 text-sm font-semibold text-gray-900">Nama Modul</h3>
  <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: 60%"></div>
  </div>
  <p class="mt-1 text-xs text-gray-500">60% selesai</p>
</div>

<!-- Status: locked -->
<div class="rounded-xl bg-gray-50 border border-gray-200 p-4 opacity-60 cursor-not-allowed">
  <!-- sama, dengan ikon gembok -->
  <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full">🔒 Terkunci</span>
</div>

<!-- Status: completed -->
<div class="rounded-xl bg-white border border-green-200 p-4 shadow-sm">
  <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">✓ Selesai</span>
</div>
```

### Badge status produk UMKM

```html
<!-- Pending -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
  Menunggu Verifikasi
</span>

<!-- Approved -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
  Disetujui
</span>

<!-- Rejected -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
  Ditolak
</span>
```

### Kartu sensor IoT

```html
<!-- Normal -->
<div class="rounded-lg border border-green-200 bg-green-50 p-3">
  <div class="flex items-center justify-between">
    <span class="text-xs text-green-700 font-medium">Suhu</span>
    <span class="w-2 h-2 rounded-full bg-green-500"></span>  <!-- dot status -->
  </div>
  <p class="text-xl font-bold text-green-900 mt-1">32°C</p>
  <p class="text-xs text-green-600">Normal</p>
</div>

<!-- Waspada — ganti green → amber -->
<!-- Bahaya — ganti green → red -->
```

### Leaderboard item

```html
<div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50">
  <!-- Posisi -->
  <span class="w-6 text-center text-sm font-bold text-gray-400">1</span>
  <!-- Avatar inisial -->
  <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-semibold text-indigo-700">
    AB
  </div>
  <!-- Nama & modul -->
  <div class="flex-1">
    <p class="text-sm font-medium text-gray-900">Andi Budiman</p>
    <p class="text-xs text-gray-500">5 modul selesai</p>
  </div>
  <!-- Poin -->
  <span class="text-sm font-semibold text-indigo-600">1.240 poin</span>
</div>
```

---

## Aturan desain

1. **Border radius**: gunakan `rounded-xl` untuk card, `rounded-lg` untuk elemen dalam card, `rounded-full` untuk badge & avatar
2. **Shadow**: gunakan `shadow-sm` default, `shadow-md` saat hover — jangan pakai shadow besar
3. **Spacing**: gunakan kelipatan 4px (Tailwind default) — p-2, p-3, p-4, gap-3, dll
4. **Animasi**: hanya `transition-shadow`, `transition-colors` yang ringan — hindari animasi berat
5. **Mobile first**: semua layout mulai dari mobile, gunakan `sm:`, `md:`, `lg:` untuk scaling
6. **Teks tombol**: selalu kapital huruf pertama saja — "Simpan perubahan", bukan "SIMPAN PERUBAHAN"
7. **Loading state**: selalu ada indikator loading untuk aksi async — gunakan Livewire `wire:loading`

---

## Filament Admin Panel

### Warna panel
```php
->colors(['primary' => Color::Indigo])
->darkMode(false)   // nonaktifkan dark mode untuk kemudahan pengguna awam
```

### Navigation groups (urutan di sidebar)
```
Admin Panel:
  1. Dashboard
  2. Nagari          (super admin only)
  3. LMS             → Modul, Kuis, Forum
  4. SDGs Desa       → Kegiatan, Dokumen
  5. UMKM            → Profil, Produk, Verifikasi
  6. IoT Sensor      → Sensor, Pembacaan
  7. Laporan         → Ekspor PDF, Ekspor Excel
  8. Pengguna        → Warga, Pemilik UMKM
  9. Pengaturan      (super admin only)

Portal Warga:
  1. Dashboard       (progress & leaderboard)
  2. Belajar         (daftar modul)
  3. Forum           (diskusi)
  4. Produk Saya     (pemilik UMKM only)
```
